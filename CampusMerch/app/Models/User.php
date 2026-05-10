<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'mobile', 'avatar_url', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'name'      => $name,
            'token'     => hash('sha256', $plainTextToken = Str::random(120)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }
}
