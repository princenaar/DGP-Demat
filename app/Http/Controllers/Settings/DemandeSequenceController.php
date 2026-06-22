<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\DemandeSequenceSynchronizer;
use Illuminate\Http\RedirectResponse;
use Throwable;

class DemandeSequenceController extends Controller
{
    public function __invoke(DemandeSequenceSynchronizer $synchronizer): RedirectResponse
    {
        try {
            $compteursCorriges = $synchronizer->resynchroniser();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('settings.index')
                ->with('error', 'La resynchronisation des séquences a échoué. Aucune modification n’a été appliquée.');
        }

        $message = $compteursCorriges === 0
            ? 'Les séquences étaient déjà synchronisées.'
            : sprintf(
                '%d compteur%s de demandes %s resynchronisé%s.',
                $compteursCorriges,
                $compteursCorriges > 1 ? 's' : '',
                $compteursCorriges > 1 ? 'ont été' : 'a été',
                $compteursCorriges > 1 ? 's' : '',
            );

        return redirect()->route('settings.index')->with('status', $message);
    }
}
