<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('letterhead')->nullable();
        });

        Schema::table('test_definitions', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('lab_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('test_definitions', function (Blueprint $table) {
            $table->dropUnique(['lab_id', 'code']);
            $table->unique(['lab_id', 'owner_user_id', 'code']);
        });

        Schema::create('user_test_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_definition_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('price_paise')->nullable();
            $table->unsignedInteger('tat_minutes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'test_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_test_settings');

        Schema::table('test_definitions', function (Blueprint $table) {
            $table->dropUnique(['lab_id', 'owner_user_id', 'code']);
            $table->unique(['lab_id', 'code']);
            $table->dropConstrainedForeignId('owner_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('letterhead');
        });
    }
};
