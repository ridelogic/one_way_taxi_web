<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(Settings $settings): View
    {
        return view('admin.settings.edit', [
            'groups' => config('settings'),
            'values' => $settings->all(),
        ]);
    }

    public function update(SettingsRequest $request, Settings $settings): RedirectResponse
    {
        $settings->setMany($request->validated());

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved. Changes are live across the website.');
    }
}
