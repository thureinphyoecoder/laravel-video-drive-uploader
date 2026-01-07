<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    protected function client(): \Google\Client
    {
        $client = new \Google\Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->setScopes([
            \Google\Service\Drive::DRIVE_FILE,
            'openid',
            'email',
            'profile',
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function redirect()
    {
        return redirect($this->client()->createAuthUrl());
    }

    public function callback()
    {
        abort_if(!request()->has('code'), 400, 'Missing auth code');

        $client = $this->client();

        $token = $client->fetchAccessTokenWithAuthCode(request('code'));

        abort_if(isset($token['error']), 401, 'Google auth failed');

        $client->setAccessToken($token);

        // Get Google profile
        $oauth = new \Google\Service\Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $googleUser->email],
            [
                'name' => $googleUser->name ?? $googleUser->email,
                'password' => bcrypt(Str::random(32)), // dummy
                'email_verified_at' => now(),
            ]
        );

        // Store tokens securely
        $user->update([
            'google_access_token' => Crypt::encryptString($token['access_token']),
            'google_refresh_token' => isset($token['refresh_token'])
                ? Crypt::encryptString($token['refresh_token'])
                : $user->google_refresh_token, // keep old refresh token
            'google_token_expires_at' => now()->addSeconds($token['expires_in']),
            'google_email' => $googleUser->email,
            'google_connected' => true,
        ]);

        Auth::login($user, true);

        return redirect()->route('home');
    }
}
