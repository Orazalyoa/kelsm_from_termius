<?php

namespace App\Services;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Universal OTP code for development
     */
    const UNIVERSAL_CODE = '1234';

    /**
     * OTP expiration time in minutes
     */
    const EXPIRATION_MINUTES = 5;

    /**
     * Generate and send email OTP
     */
    public static function sendEmailOTP(string $email, string $purpose = 'verification'): array
    {
        // Delete expired OTPs for this identifier
        self::cleanupExpiredOTPs($email, 'email', $purpose);

        // Generate OTP code (using universal code for now)
        $code = self::UNIVERSAL_CODE;

        // Store OTP in database
        $otp = OtpCode::create([
            'identifier' => $email,
            'type' => 'email',
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(self::EXPIRATION_MINUTES),
        ]);

        // TODO: Send actual email when integrated with email service
        // For now, just log it
        Log::info("OTP Code for {$email}: {$code} (expires in " . self::EXPIRATION_MINUTES . " minutes)");

        return [
            'success' => true,
            'message' => 'OTP sent to email',
            'expires_in' => self::EXPIRATION_MINUTES * 60, // in seconds
        ];
    }

    /**
     * Generate and send phone OTP
     */
    public static function sendPhoneOTP(string $phone, string $purpose = 'verification'): array
    {
        // Delete expired OTPs for this identifier
        self::cleanupExpiredOTPs($phone, 'phone', $purpose);

        // Generate OTP code (using universal code for now)
        $code = self::UNIVERSAL_CODE;

        // Store OTP in database
        $otp = OtpCode::create([
            'identifier' => $phone,
            'type' => 'phone',
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(self::EXPIRATION_MINUTES),
        ]);

        // TODO: Send actual SMS when integrated with SMS service
        // For now, just log it
        Log::info("OTP Code for {$phone}: {$code} (expires in " . self::EXPIRATION_MINUTES . " minutes)");

        return [
            'success' => true,
            'message' => 'OTP sent to phone',
            'expires_in' => self::EXPIRATION_MINUTES * 60, // in seconds
        ];
    }

    /**
     * Verify email OTP
     */
    public static function verifyEmailOTP(string $email, string $code, string $purpose = 'verification'): bool
    {
        return self::verifyOTP($email, 'email', $code, $purpose);
    }

    /**
     * Verify phone OTP
     */
    public static function verifyPhoneOTP(string $phone, string $code, string $purpose = 'verification'): bool
    {
        return self::verifyOTP($phone, 'phone', $code, $purpose);
    }

    /**
     * Generic OTP verification
     */
    protected static function verifyOTP(string $identifier, string $type, string $code, string $purpose): bool
    {
        $otp = OtpCode::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return false;
        }

        // Mark OTP as used
        $otp->markAsUsed();

        return true;
    }

    /**
     * Check if OTP exists and is valid (without marking as used)
     */
    public static function checkOTPValid(string $identifier, string $type, string $code, string $purpose = 'verification'): bool
    {
        return OtpCode::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Cleanup expired OTPs for an identifier
     */
    protected static function cleanupExpiredOTPs(string $identifier, string $type, string $purpose): void
    {
        OtpCode::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->where(function ($query) {
                $query->where('expires_at', '<', Carbon::now())
                      ->orWhere('is_used', true);
            })
            ->delete();
    }

    /**
     * Generate random OTP code (for future use)
     */
    protected static function generateRandomCode(int $length = 6): string
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

