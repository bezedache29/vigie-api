<?php

namespace App\Console\Commands;

use App\Mail\DigestMail;
use App\Models\Digest;
use App\Models\User;
use App\Services\DigestBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('vigie:send-digests')]
#[Description('Envoie le digest email aux utilisateurs pour qui il est dû, selon leur fréquence et préférences')]
class SendDigestsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DigestBuilder $builder): int
    {
        $sent = 0;
        $failed = 0;

        User::query()->each(function (User $user) use ($builder, &$sent, &$failed) {
            if (! $builder->isDue($user)) {
                return;
            }

            $items = $builder->eligibleItems($user);

            if ($items->isEmpty()) {
                return;
            }

            // Envoi synchrone et volontairement non queued : si l'envoi
            // échoue, on ne doit pas enregistrer le Digest, sinon isDue()
            // considérerait à tort le digest comme envoyé et ne réessaierait
            // jamais au prochain run planifié.
            try {
                Mail::to($user)->send(new DigestMail($items));
            } catch (Throwable $e) {
                $failed++;
                Log::error("Échec d'envoi du digest pour l'utilisateur [{$user->id}] : {$e->getMessage()}");

                return;
            }

            Digest::create([
                'user_id' => $user->id,
                'item_ids' => $items->pluck('id')->all(),
                'channel' => 'email',
                'sent_at' => now(),
            ]);

            $sent++;
        });

        $this->info("{$sent} digest(s) envoyé(s), {$failed} échec(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
