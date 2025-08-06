<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    /**
     * Enable 2FA for a user.
     */
    public function enable(User $user): array
    {
        $secret = $this->generateSecret();
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
            'two_factor_enabled' => true,
        ]);

        return [
            'secret' => $secret,
            'qr_code' => $this->generateQrCode($user->email, $secret),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
        ]);
    }

    /**
     * Verify 2FA code.
     */
    public function verify(User $user, string $code): bool
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        // Check if it's a recovery code
        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_diff($recoveryCodes, [$code]);
            $user->update(['two_factor_recovery_codes' => json_encode(array_values($recoveryCodes))]);
            return true;
        }

        // Verify TOTP code
        return $this->verifyTOTP($user->two_factor_secret, $code);
    }

    /**
     * Generate a new secret key.
     */
    private function generateSecret(): string
    {
        return Str::random(32);
    }

    /**
     * Generate recovery codes.
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(8) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(12);
        }
        return $codes;
    }

    /**
     * Generate QR code for 2FA setup.
     */
    private function generateQrCode(string $email, string $secret): string
    {
        $otpauthUrl = "otpauth://totp/{$email}?secret={$secret}&issuer=EateryAPI";

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($otpauthUrl);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Verify TOTP code.
     */
    private function verifyTOTP(string $secret, string $code): bool
    {
        // Simple TOTP verification (you might want to use a proper TOTP library)
        $timeSlice = floor(time() / 30);
        
        // Generate expected codes for current and adjacent time slices
        $expectedCodes = [];
        for ($i = -1; $i <= 1; $i++) {
            $expectedCodes[] = $this->generateTOTP($secret, $timeSlice + $i);
        }

        return in_array($code, $expectedCodes);
    }

    /**
     * Generate TOTP code.
     */
    private function generateTOTP(string $secret, int $timeSlice): string
    {
        $hash = hash_hmac('sha1', pack('N*', $timeSlice), $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
} 