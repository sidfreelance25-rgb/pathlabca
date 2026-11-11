<?php

use App\Models\TestDefinition;
use App\Models\User;
use App\Models\UserTestSetting;
use App\Services\TestCloner;
use App\Support\Letterhead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letterhead_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_letterhead_id')->nullable()->constrained('letterhead_templates')->nullOnDelete();
            $table->string('name');
            $table->json('design');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['lab_id', 'owner_user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('active_letterhead_id')->nullable()->after('letterhead')->constrained('letterhead_templates')->nullOnDelete();
        });

        Schema::table('test_definitions', function (Blueprint $table) {
            $table->foreignId('source_test_id')->nullable()->after('owner_user_id')->constrained('test_definitions')->nullOnDelete();
            $table->index(['owner_user_id', 'source_test_id']);
        });

        foreach (DB::table('labs')->get() as $lab) {
            $labId = data_get($lab, 'id');
            if (! is_numeric($labId)) {
                continue;
            }

            $design = json_encode(Letterhead::defaultsFromLab($lab));
            if (! is_string($design)) {
                continue;
            }

            DB::table('letterhead_templates')->insert([
                'lab_id' => (int) $labId,
                'owner_user_id' => null,
                'source_letterhead_id' => null,
                'name' => 'Laboratory letterhead',
                'design' => $design,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->whereNotNull('letterhead')->get() as $user) {
            $userId = data_get($user, 'id');
            $labId = data_get($user, 'lab_id');
            $saved = data_get($user, 'letterhead');
            if (! is_numeric($userId) || ! is_numeric($labId) || ! is_string($saved)) {
                continue;
            }

            $personalId = DB::table('letterhead_templates')->insertGetId([
                'lab_id' => (int) $labId,
                'owner_user_id' => (int) $userId,
                'source_letterhead_id' => null,
                'name' => 'My letterhead',
                'design' => $saved,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('users')->where('id', $userId)->update(['active_letterhead_id' => $personalId]);
        }

        $cloner = app(TestCloner::class);
        UserTestSetting::query()->with('test')->each(function (UserTestSetting $setting) use ($cloner): void {
            $source = $setting->test;
            $user = User::query()->find($setting->user_id);

            if (! $source instanceof TestDefinition || $user === null || $source->owner_user_id !== null) {
                return;
            }

            $clone = $cloner->cloneFor($user, $source);
            $clone->update([
                'name' => $setting->name ?: $clone->name,
                'price_paise' => $setting->price_paise ?? $clone->price_paise,
                'active' => $setting->active,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_letterhead_id');
        });

        Schema::table('test_definitions', function (Blueprint $table) {
            $table->dropIndex(['owner_user_id', 'source_test_id']);
            $table->dropConstrainedForeignId('source_test_id');
        });

        Schema::dropIfExists('letterhead_templates');
    }
};
