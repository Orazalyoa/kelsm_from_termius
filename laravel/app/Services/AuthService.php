<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user
     *
     * @param array $data
     * @return User
     */
    public static function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        
        // No transaction here - let the caller handle transactions
        $user = User::create($data);
        
        // Attach professions if provided
        if (!empty($data['profession_ids'])) {
            $user->professions()->attach($data['profession_ids']);
        }
        
        return $user;
    }

    /**
     * Update user profile
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public static function updateProfile(User $user, array $data)
    {
        // Remove password from data if not provided
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        DB::beginTransaction();
        try {
            $user->update($data);
            
            // Update professions if provided
            if (isset($data['profession_ids'])) {
                $user->professions()->sync($data['profession_ids']);
            }
            
            DB::commit();
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Change user password
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public static function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        return true;
    }
}
