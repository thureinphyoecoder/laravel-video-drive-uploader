<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Google\Client;
use Google\Service\Oauth2;
use Google\Service\Drive;

class GoogleAuthController extends Controller
{
    protected function client(): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setScopes([Drive::DRIVE_FILE, 'openid', 'email', 'profile']);
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
        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode(request('code'));
        $client->setAccessToken($token);

        $googleUser = (new Oauth2($client))->userinfo->get();

        $user = User::firstOrCreate(['email' => $googleUser->email], [
            'name' => $googleUser->name ?? $googleUser->email,
            'password' => bcrypt(Str::random(32)),
        ]);

        $user->update([
            'google_access_token' => Crypt::encryptString($token['access_token']),
            'google_refresh_token' => isset($token['refresh_token']) ? Crypt::encryptString($token['refresh_token']) : $user->google_refresh_token,
            'google_token_expires_at' => now()->addSeconds($token['expires_in']),
        ]);

        Auth::login($user, true);
        return redirect()->route('home');
    }
}
