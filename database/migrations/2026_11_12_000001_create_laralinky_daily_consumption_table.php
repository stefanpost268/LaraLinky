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
        $prefix = Config::string('laralinky.storage.database.table_prefix', 'laralinky_');
        Schema::connection(is_string(Config::get('laralinky.storage.database.connection')) ? Config::get('laralinky.storage.database.connection') : null)->create($prefix.'daily_consumption', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->string('prm');
            $table->dateTime('measured_at');
            $table->decimal('value', 16, 4)->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
            $table->unique(['prm', 'measured_at']);
            $table->foreign('prm')->references('prm')->on($prefix.'meters')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $tableName = Config::string('laralinky.storage.database.table_prefix', 'laralinky_').'daily_consumption';
        Schema::connection(is_string(Config::get('laralinky.storage.database.connection')) ? Config::get('laralinky.storage.database.connection') : null)->dropIfExists($tableName);
    }
};
