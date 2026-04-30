<?php

namespace App\Console\Commands;

use App\Mail\ResumeQuotidienMail;
use App\Models\User;
use App\Services\DemandeBacklogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('resume:quotidien')]
#[Description('Envoie le résumé quotidien des demandes à traiter par utilisateur.')]
class EnvoyerResumeQuotidien extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DemandeBacklogService $backlogService): int
    {
        $sent = 0;

        User::query()
            ->whereNotNull('email')
            ->get()
            ->each(function (User $user) use ($backlogService, &$sent): void {
                $demandes = $backlogService->demandesATraiter($user);

                if ($demandes->isEmpty()) {
                    return;
                }

                Mail::to($user->email)->queue(new ResumeQuotidienMail($user, $demandes));
                $sent++;
            });

        $this->info("{$sent} résumé(s) quotidien(s) envoyé(s).");

        return self::SUCCESS;
    }
}
