<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\User;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        if ($provider === 'github') {
            return Socialite::driver('github')
                ->scopes(['user:email'])
                ->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        // User Create / Login

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName(),
                'avatar' => $socialUser->getAvatar(),
            ]
        );

        $token = $user->createToken('auth')->plainTextToken;

        return redirect(env('FRONTEND_URL')."/auth/social?token=".$token);
    }













    // login with google
    public function loginWithFacebook()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            $user = User::where('facebook_id', $facebookUser->id)->first();

            if (!$user) {
                $user = User::where('email', $facebookUser->email)->first();

                if ($user) {
                    $user->facebook_id = $facebookUser->id;
                    $user->save();
                } else {
                    $user = User::create([
                        'name' => $facebookUser->name,
                        'email' => $facebookUser->email,
                        'facebook_id' => $facebookUser->id,
                        'photo' => $facebookUser->avatar_original,
                        'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    ]);
                }
            }

            Auth::login($user);

            $token = $user->createToken('social-login')->plainTextToken;

            return redirect(env('FRONTEND_URL') . '/auth/social?token=' . urlencode($token));

        } catch (\Throwable $e) {

            return redirect(
                env('FRONTEND_URL') .
                '/login?error=' . urlencode($e->getMessage())
            );
        }
    }

    // login with google
    public function loginWithGoogle()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser->getEmail()) {
                return redirect(
                    env('FRONTEND_URL') .
                    '/login?error=' . urlencode('Google account has no email address.')
                );
            }

            DB::beginTransaction();

            $user = User::where('google_id', $googleUser->getId())->first();

            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                } else {
                    $user = User::create([
                        'name'      => $googleUser->getName(),
                        'email'     => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'photo'     => $googleUser->getAvatar(),
                        'password'  => bcrypt(Str::random(32)),
                    ]);
                }
            }

            Auth::login($user);

            $token = $user->createToken('social-login')->plainTextToken;

            DB::commit();

            return redirect(
                env('FRONTEND_URL') .
                '/auth/social?token=' . urlencode($token)
            );

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Google Login Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return redirect(
                env('FRONTEND_URL') .
                '/login?error=' . urlencode('Google login failed. Please try again.')
            );
        }
    }

    // login with github
    public function loginWithGithub() {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            if (!$githubUser->getEmail()) {
                return redirect(
                    env('FRONTEND_URL') .
                    '/login?error=' . urlencode('GitHub account has no public email address.')
                );
            }

            DB::beginTransaction();

            // First check by github_id
            $user = User::where('github_id', $githubUser->getId())->first();

            if (!$user) {

                // Check existing account by email
                $user = User::where('email', $githubUser->getEmail())->first();

                if ($user) {

                    // Link GitHub account
                    $user->github_id = $githubUser->getId();

                    if (!$user->photo && $githubUser->getAvatar()) {
                        $user->photo = $githubUser->getAvatar();
                    }

                    $user->save();

                } else {

                    // Create new user
                    $user = User::create([
                        'name'      => $githubUser->getName() ?: $githubUser->getNickname(),
                        'email'     => $githubUser->getEmail(),
                        'github_id' => $githubUser->getId(),
                        'photo'     => $githubUser->getAvatar(),
                        'password'  => bcrypt(Str::random(32)),
                    ]);
                }
            }

            Auth::login($user);

            $token = $user->createToken('social-login')->plainTextToken;

            DB::commit();

            return redirect(
                env('FRONTEND_URL') .
                '/auth/social?token=' . urlencode($token)
            );

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('GitHub Login Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return redirect(
                env('FRONTEND_URL') .
                '/login?error=' . urlencode('GitHub login failed. Please try again.')
            );
        }
    }
}
