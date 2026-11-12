<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = Config::string('laralinky.storage.database.table_prefix', 'laralinky_').'meters';
        Schema::connection(is_string(Config::get('laralinky.storage.database.connection')) ? Config::get('laralinky.storage.database.connection') : null)->create($tableName, function (Blueprint $table): void {
            $table->string('prm')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = Config::string('laralinky.storage.database.table_prefix', 'laralinky_').'meters';
        Schema::connection(is_string(Config::get('laralinky.storage.database.connection')) ? Config::get('laralinky.storage.database.connection') : null)->dropIfExists($tableName);
    }
};
