<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function generalSettings(): View
    {
        return view('admin.settings.general', ['timezones' => timezone_identifiers_list()]);
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        return back()->with('success', __('Cache cleared successfully'));
    }

    public function locale(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');
        if (array_key_exists($locale, SetLocale::LOCALES)) {
            $request->session()->put('locale', $locale);
        }

        return back();
    }

    public function profile(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar_upload_id' => ['nullable', 'integer', 'exists:uploads,id'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $data['phone'] = cleanBdPhone($data['phone'] ?? null);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        unset($data['current_password']);

        $user->update($data);

        return back()->with('success', __('Profile updated successfully'));
    }
}
