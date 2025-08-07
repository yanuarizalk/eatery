<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Output\ConsoleOutput;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramService
{
    /**
     * The Telegram bot instance.
     *
     * @var Api
     */
    protected $telegram;

    /**
     * The last update ID.
     *
     * @var int
     */
    protected $lastUpdateId = 0;

    /**
     * The chat states.
     *
     * @var array
     */
    protected $chatStates = [];

    /**
     * Create a new command instance.
     *
     * @return void
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bots.mybot.token'));
    }

    /**
     * @param string $method
     * @param string $uri
     * @param int $chatId
     * @param array $data
     * @return \Illuminate\Http\Client\Response|null
     */
    private function makeApiRequest(string $method, string $uri, int $chatId, array $data = []): ?\Illuminate\Http\Client\Response
    {
        $token = Cache::get("telegram_token_{$chatId}");

        try {
            $response = Http::baseUrl(config('app.url') . '/api')
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->{$method}($uri, $data);
        } catch (ConnectionException $e) {
            Log::error('API connection failed', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, 'Sorry, I could not connect to the server. Please try again later.');
            return null;
        }

        return $response;
    }

    /**
     * Process the update.
     *
     * @param \Telegram\Bot\Objects\Update $update
     */
    public function processUpdate($update)
    {
        if ($update->getMessage()) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $output = new ConsoleOutput();
            $output->writeln("chatId: {$chatId} Message ID: {$message->getMessageId()} message: {$message->getText()}");
            $text = $message->getText();

            if ($text) {
                if ($this->isCommand($text)) {
                    $this->handleCommand($chatId, $text);
                } else {
                    if (isset($this->chatStates[$chatId])) {
                        $this->handleState($chatId, $text);
                    } else {
                        $this->handleConversation($chatId, $text);
                    }
                }
            }
        }
    }

    /**
     * Check if the text is a command.
     *
     * @param string $text
     * @return bool
     */
    protected function isCommand($text)
    {
        return str_starts_with($text, '/');
    }

    /**
     * Handle the command.
     *
     * @param int $chatId
     * @param string $command
     */
    protected function handleCommand($chatId, $command)
    {
        $commandParts = explode(' ', $command);
        $commandName = $commandParts[0];

        switch ($commandName) {
            case '/register':
                $this->chatStates[$chatId] = ['command' => 'register', 'step' => 'name'];
                $this->sendMessage($chatId, "Please enter your name:");
                break;
            case '/login':
                $this->chatStates[$chatId] = ['command' => 'login', 'step' => 'email'];
                $this->sendMessage($chatId, "Please enter your email:");
                break;
            case '/logout':
                $this->logout($chatId);
                break;
            case '/refresh':
                $this->refresh($chatId);
                break;
            case '/me':
                $this->me($chatId);
                break;
            case '/enable2fa':
                $this->enable2FA($chatId);
                break;
            case '/disable2fa':
                $this->chatStates[$chatId] = ['command' => 'disable2fa', 'step' => 'code'];
                $this->sendMessage($chatId, "Please enter your 2FA code to disable:");
                break;
            case '/verify2fa':
                $this->chatStates[$chatId] = ['command' => 'verify2fa', 'step' => 'code'];
                $this->sendMessage($chatId, "Please enter your 2FA code to verify:");
                break;
            case '/index':
                $this->getRestaurants($chatId);
                break;
            default:
                $this->sendMessage($chatId, "Unknown command.");
                break;
        }
    }

    /**
     * Handle the state.
     *
     * @param int $chatId
     * @param string $text
     */
    protected function handleState($chatId, $text)
    {
        $state = $this->chatStates[$chatId];
        $command = $state['command'];
        $step = $state['step'];

        switch ($command) {
            case 'register':
                $this->handleRegistrationState($chatId, $text, $step);
                break;
            case 'login':
                $this->handleLoginState($chatId, $text, $step);
                break;
            case 'disable2fa':
                $this->disable2FA($chatId, $text);
                break;
            case 'verify2fa':
                $this->verify2FA($chatId, $text);
                break;
        }
    }

    /**
     * Handle the registration state.
     *
     * @param int $chatId
     * @param string $text
     * @param string $step
     */
    protected function handleRegistrationState($chatId, $text, $step)
    {
        switch ($step) {
            case 'name':
                $this->chatStates[$chatId]['name'] = $text;
                $this->chatStates[$chatId]['step'] = 'email';
                $this->sendMessage($chatId, "Please enter your email:");
                break;
            case 'email':
                $this->chatStates[$chatId]['email'] = $text;
                $this->chatStates[$chatId]['step'] = 'password';
                $this->sendMessage($chatId, "Please enter your password:");
                break;
            case 'password':
                $this->chatStates[$chatId]['password'] = $text;
                $this->register($chatId, $this->chatStates[$chatId]['name'], $this->chatStates[$chatId]['email'], $text);
                unset($this->chatStates[$chatId]);
                break;
        }
    }

    /**
     * Handle the login state.
     *
     * @param int $chatId
     * @param string $text
     * @param string $step
     */
    protected function handleLoginState($chatId, $text, $step)
    {
        switch ($step) {
            case 'email':
                $this->chatStates[$chatId]['email'] = $text;
                $this->chatStates[$chatId]['step'] = 'password';
                $this->sendMessage($chatId, "Please enter your password:");
                break;
            case 'password':
                $this->chatStates[$chatId]['password'] = $text;
                $this->login($chatId, $this->chatStates[$chatId]['email'], $text);
                break;
        }
    }

    /**
     * Handle the conversation.
     *
     * @param int $chatId
     * @param string $text
     */
    protected function handleConversation($chatId, $text)
    {
        $response = $this->makeApiRequest('get', '/restaurants/search', $chatId, ['q' => $text]);
        $botName = config("telegram.default");

        if ($response && $response->successful()) {
            $restaurants = $response->json('data.restaurants');
            if (!empty($restaurants)) {
                $message = "Here are the restaurants I found:\n\n";
                foreach ($restaurants as $restaurant) {
                    $state = "";
                    if (isset($restaurant['opening_hours']) && isset($restaurant['opening_hours']['open_now']))
                        $state = $restaurant['opening_hours']['open_now'] == true ? "Open" : "Close";
                    $message .= "*{$restaurant['name']}*\n";
                    $message .= "Rating: {$restaurant['rating']} ⭐\n";
                    if (isset($state) && $state != "") $message .= "State: {$state} \n";
                    $message .= "Phone: {$restaurant['phone']} \n";
                    $message .= "Address: {$restaurant['address']} \n";
                    $message .= "[See review](https://t.me/{$botName}?start=/review {$restaurant['id']})  [View map](https://t.me/{$botName}?start=/map {$restaurant['id']})\n";
                    $message .= "____________________________________________________ \n";
                    // $message .= "Price: " . $restaurant['price_level'] . "\n";
                    // $message .= "Cuisine: {$restaurant['cuisine_type']}\n\n";
                }
            } else {
                $message = "I couldn't find any restaurants matching your search.";
            }

            $message = (substr($message, 0, 4000));
            $this->sendMessage($chatId, $message, 'Markdown');
        } elseif ($response) {
            Log::error('Restaurant search failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Sorry, I couldn't search for restaurants at the moment. " . $response->json()['message']);
        }
    }


    /**
     * Register a user.
     *
     * @param int $chatId
     * @param string $name
     * @param string $email
     * @param string $password
     */
    protected function register($chatId, $name, $email, $password)
    {
        $response = $this->makeApiRequest('post', '/auth/register', $chatId, [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password
        ]);

        if ($response && $response->successful()) {
            $this->sendMessage($chatId, "Registration successful!");
        } elseif ($response) {
            Log::error('Registration failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Registration failed: " . $response->json()['message']);
        }
    }

    /**
     * Login a user.
     *
     * @param int $chatId
     * @param string $email
     * @param string $password
     */
    protected function login($chatId, $email, $password)
    {
        $response = $this->makeApiRequest('post', '/auth/login', $chatId, [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();
            if (isset($data['data']['two_factor_enabled']) && $data['data']['two_factor_enabled']) {
                $this->chatStates[$chatId] = ['command' => 'verify2fa', 'step' => 'code'];
                $this->sendMessage($chatId, "Please enter your 2FA code:");
            } else {
                $token = $data['data']['token'];
                Cache::put("telegram_token_{$chatId}", $token, now()->addMinutes(60));
                $this->sendMessage($chatId, "Login successful!");
                unset($this->chatStates[$chatId]);
            }
        } elseif ($response) {
            Log::error('Login failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Login failed: " . $response->json()['message']);
            unset($this->chatStates[$chatId]);
        }
    }

    /**
     * Logout a user.
     *
     * @param int $chatId
     */
    protected function logout($chatId)
    {
        $response = $this->makeApiRequest('post', '/auth/logout', $chatId);

        if ($response && $response->successful()) {
            Cache::forget("telegram_token_{$chatId}");
            $this->sendMessage($chatId, "Logout successful!");
        } elseif ($response) {
            Log::error('Logout failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Logout failed: " . $response->json()['message']);
        }
    }

    /**
     * Refresh token.
     *
     * @param int $chatId
     */
    protected function refresh($chatId)
    {
        $response = $this->makeApiRequest('post', '/auth/refresh', $chatId);

        if ($response && $response->successful()) {
            $token = $response->json('data.access_token');
            Cache::put("telegram_token_{$chatId}", $token, now()->addMinutes(60));
            $this->sendMessage($chatId, "Token refreshed successfully!");
        } elseif ($response) {
            Log::error('Refresh failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Refresh failed: " . $response->json()['message']);
        }
    }

    /**
     * Get user details.
     *
     * @param int $chatId
     */
    protected function me($chatId)
    {
        $response = $this->makeApiRequest('get', '/auth/me', $chatId);

        if ($response && $response->successful()) {
            $user = $response->json();
            $message = "Your details:\n";
            $message .= "*Name:* " . $user['name'] . "\n";
            $message .= "*Email:* " . $user['email'] . "\n";
            $this->sendMessage($chatId, $message, 'Markdown');
        } elseif ($response) {
            Log::error('Me failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Failed to get user details: " . $response->json()['message']);
        }
    }

    /**
     * Enable 2FA.
     *
     * @param int $chatId
     */
    protected function enable2FA($chatId)
    {
        $response = $this->makeApiRequest('post', '/auth/2fa/enable', $chatId);

        if ($response && $response->successful()) {
            $data = $response->json();
            $this->sendMessage($chatId, "2FA enabled successfully. Add this code into your authenticator app.\n");
            $this->sendMessage($chatId, "{$data['data']['secret_code']}\n");

            // $this->sendMessage($chatId, "2FA enabled successfully. Scan this QR code with your authenticator app.");
            // $this->sendPhoto($chatId, $data['qr_code_url']);

            $this->sendMessage($chatId, "Your recovery codes: \n" . implode("\n", $data['data']['recovery_codes']));
        } elseif ($response) {
            Log::error('Enable 2FA failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Failed to enable 2FA: " . $response->json()['message']);
        }
    }

    /**
     * Disable 2FA.
     *
     * @param int $chatId
     * @param string $code
     */
    protected function disable2FA($chatId, $code)
    {
        $response = $this->makeApiRequest('post', '/auth/2fa/disable', $chatId, ['code' => $code]);

        if ($response && $response->successful()) {
            $this->sendMessage($chatId, "2FA disabled successfully.");
        } elseif ($response) {
            Log::error('Disable 2FA failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Failed to disable 2FA: " . $response->json()['message']);
        }
        unset($this->chatStates[$chatId]);
    }

    /**
     * Verify 2FA.
     *
     * @param int $chatId
     * @param string $code
     */
    protected function verify2FA($chatId, $code)
    {
        $response = $this->makeApiRequest('post', '/auth/2fa/verify', $chatId, ['code' => $code]);

        if ($response && $response->successful()) {
            $token = $response['data']['token'];
            Cache::put("telegram_token_{$chatId}", $token, now()->addMinutes(60));
            $this->sendMessage($chatId, "2FA verified successfully.");
        } elseif ($response) {
            Log::error('Verify 2FA failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Failed to verify 2FA: " . $response->json()['message']);
        }
        unset($this->chatStates[$chatId]);
    }

    /**
     * Get restaurants.
     *
     * @param int $chatId
     */
    protected function getRestaurants($chatId)
    {
        $response = $this->makeApiRequest('get', '/restaurants', $chatId);

        if ($response && $response->successful()) {
            $restaurants = $response->json('data.restaurants');
            if (!empty($restaurants)) {
                $message = "Here are the available restaurants:\n\n";
                foreach ($restaurants as $restaurant) {
                    $message .= "*{$restaurant['name']}*\n";
                    $message .= "Rating: {$restaurant['rating']} ⭐\n";
                    $message .= "Price: " . str_repeat('$', $restaurant['price_level']) . "\n";
                    $message .= "Cuisine: {$restaurant['cuisine_type']}\n\n";
                }
            } else {
                $message = "No restaurants found.";
            }

            $this->sendMessage($chatId, $message, 'Markdown');
        } elseif ($response) {
            Log::error('Get restaurants failed', ['response' => $response->body()]);
            $this->sendMessage($chatId, "Sorry, I couldn't fetch restaurants at the moment. " . $response->json()['message']);
        }
    }

    /**
     * Send a message to the chat.
     *
     * @param int $chatId
     * @param string $text
     * @param string|null $parseMode
     */
    public function sendMessage($chatId, $text, $parseMode = null)
    {
        try {
            $data = [
                'chat_id' => $chatId,
                'text' => $text,
            ];
            if ($parseMode) {
                $data['parse_mode'] = $parseMode;
            }
            $this->telegram->sendMessage($data);
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send a photo to the chat.
     *
     * @param int $chatId
     * @param string $photoUrl
     */
    public function sendPhoto($chatId, $photoUrl)
    {
        try {
            $this->telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => $photoUrl,
            ]);
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send photo', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get updates from Telegram.
     *
     * @return \Telegram\Bot\Objects\Update[]
     */
    public function getUpdates(): array
    {
        try {
            $offset = $this->lastUpdateId + 1;
            $updates = $this->telegram->getUpdates(['offset' => $offset, 'timeout' => 30]);

            if (!empty($updates)) {
                $this->lastUpdateId = end($updates)->getUpdateId();
            }
        } catch (TelegramSDKException $e) {
            Log::error('Failed to get updates', ['error' => $e->getMessage()]);
            return [];
        }


        return $updates;
    }
}