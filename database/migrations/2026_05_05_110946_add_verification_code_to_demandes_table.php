<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var string
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->string('verification_code', 9)->nullable()->after('code_qr')->unique();
        });

        $usedCodes = DB::table('demandes')
            ->whereNotNull('verification_code')
            ->pluck('verification_code')
            ->flip()
            ->all();

        DB::table('demandes')
            ->whereNotNull('code_qr')
            ->whereNull('verification_code')
            ->orderBy('id')
            ->select(['id'])
            ->get()
            ->each(function (object $demande) use (&$usedCodes): void {
                $code = $this->generateVerificationCode($usedCodes);
                $usedCodes[$code] = true;

                DB::table('demandes')
                    ->where('id', $demande->id)
                    ->update(['verification_code' => $code]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropUnique('demandes_verification_code_unique');
            $table->dropColumn('verification_code');
        });
    }

    /**
     * @param  array<string, bool>  $usedCodes
     */
    private function generateVerificationCode(array $usedCodes): string
    {
        do {
            $code = $this->randomChunk().'-'.$this->randomChunk();
        } while (isset($usedCodes[$code]));

        return $code;
    }

    private function randomChunk(): string
    {
        $chunk = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < 4; $index++) {
            $chunk .= self::ALPHABET[random_int(0, $max)];
        }

        return $chunk;
    }
};
