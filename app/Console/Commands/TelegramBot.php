<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Telegram bot';

    /**
     * The Telegram service.
     *
     * @var TelegramService
     */
    protected $telegramService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bot started...');
        while (true) {
            try {
                $updates = $this->telegramService->getUpdates();
                foreach ($updates as $update) {
                    $this->telegramService->processUpdate($update);
                }
            } catch (TelegramSDKException $e) {
                $this->error($e->getMessage());
            }
        }
    }
}
