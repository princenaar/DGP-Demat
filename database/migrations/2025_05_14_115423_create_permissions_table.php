<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), new Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.'));

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            // $table->engine('InnoDB');
            $table->bigIncrements('id'); // permission id
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::drop($tableNames['permissions']);
    }
};
