<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function register(array $data): array
    {
        $email = strtolower(trim((string) $data['email']));

        if (User::where('email', $email)->exists()) {
            throw new BusinessException('Email already exists.', ['email' => ['The email is already registered.']], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
        ]);

        $tokenValue = $this->generateToken();

        Token::create([
            'user_id' => $user->_id,
            'token' => $tokenValue,
            'revoked_at' => null,
        ]);

        return [
            'user' => $user->makeHidden(['password']),
            'token' => $tokenValue,
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', strtolower(trim((string) $data['email'])))->first();

        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            throw new BusinessException('Invalid credentials.', ['email' => ['The provided credentials are incorrect.']], 401);
        }

        $tokenValue = $this->generateToken();

        Token::query()->where('user_id', $user->_id)->update(['revoked_at' => now()]);

        Token::create([
            'user_id' => $user->_id,
            'token' => $tokenValue,
            'revoked_at' => null,
        ]);

        return [
            'user' => $user->makeHidden(['password']),
            'token' => $tokenValue,
        ];
    }

    public function logout(User $user, ?string $tokenValue = null): void
    {
        $query = Token::query()->where('user_id', $user->_id);

        if ($tokenValue) {
            $query->where('token', $tokenValue);
        }

        $query->update(['revoked_at' => now()]);
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
