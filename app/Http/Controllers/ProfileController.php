<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle Superbuy Cookie / cURL parsing
        if (isset($validated['superbuy_cookie']))
        {
            $input = trim($validated['superbuy_cookie']);

            // Check for curl command pattern
            if (stripos($input, 'curl ') !== false || stripos($input, 'curl.exe') !== false)
            {
                $cookieFound = false;
                $uaFound = false;

                // Extract cookie (-H "cookie: ..." or -b "...")
                // Improved regex to handle optional spaces and different quoting
                if (
                    preg_match('/(?:-H|-b)\s+["\']?cookie:\s*([^"\']+)["\']?/i', $input, $matches) ||
                    preg_match('/(?:-H|-b)\s+["\']?([^"\']+)["\']?/i', $input, $matchesB)
                )
                {
                    // Start with matchesB if matches empty (for -b case without cookie: prefix which is rare but possible in some formats, but -b usually takes a file or string)
                    // Actually -b "name=val" is standard.
                    // Let's stick to the explicit Cookie header first.
                    if (!empty($matches[1]))
                    {
                        $validated['superbuy_cookie'] = \Illuminate\Support\Str::of($matches[1])->replace('^', '')->toString();
                        $cookieFound = true;
                    }
                    elseif (!empty($matchesB[1]) && stripos($matchesB[0], 'cookie') === false)
                    {
                        // If -b matched but check if it's not just a flag
                        // simplified:
                    }
                }

                // Fallback specific simple regexes if complex one fails
                if (!$cookieFound)
                {
                    if (preg_match('/-H\s+["\']?cookie:\s*([^"\']+)["\']?/i', $input, $matches))
                    {
                        $validated['superbuy_cookie'] = \Illuminate\Support\Str::of($matches[1])->replace('^', '')->toString();
                    }
                    elseif (preg_match('/-b\s+["\']?([^"\']+)["\']?/', $input, $matches))
                    {
                        $validated['superbuy_cookie'] = \Illuminate\Support\Str::of($matches[1])->replace('^', '')->toString();
                    }
                }

                // Extract user-agent
                if (preg_match('/-H\s+["\']?user-agent:\s*([^"\']+)["\']?/i', $input, $matches))
                {
                    $validated['superbuy_user_agent'] = \Illuminate\Support\Str::of($matches[1])->replace('^', '')->toString();
                    $uaFound = true;
                }
            }

            // If empty, clear UA as well
            if (empty($validated['superbuy_cookie']))
            {
                $validated['superbuy_user_agent'] = null;
            }
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email'))
        {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
