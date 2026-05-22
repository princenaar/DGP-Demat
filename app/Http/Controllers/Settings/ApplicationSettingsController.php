<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ApplicationSettingsRequest;
use App\Models\ApplicationSetting;
use Illuminate\Http\RedirectResponse;

class ApplicationSettingsController extends Controller
{
    public function update(ApplicationSettingsRequest $request): RedirectResponse
    {
        ApplicationSetting::setComplementLinkValidityDays((int) $request->validated('complement_link_validity_days'));

        return redirect()->route('settings.index')->with('status', 'Paramètres applicatifs mis à jour.');
    }
}
