<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('licence_number')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 12);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->unique(['lab_id', 'code']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->timestamps();
            $table->unique(['lab_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('lab_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('lab_id')->constrained()->nullOnDelete();
            $table->string('role')->nullable()->after('email');
            $table->string('designation')->nullable();
            $table->string('qualification')->nullable();
        });

        Schema::create('specimen_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('container')->nullable();
            $table->string('additive')->nullable();
            $table->text('handling_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('test_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specimen_type_id')->constrained()->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('loinc')->nullable();
            $table->text('synonyms')->nullable();
            $table->string('method')->nullable();
            $table->boolean('is_panel')->default(false);
            $table->unsignedInteger('price_paise')->default(0);
            $table->unsignedInteger('tat_minutes')->default(240);
            $table->string('report_section');
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedInteger('catalogue_version')->default(1);
            $table->boolean('active')->default(true);
            $table->text('collection_notes')->nullable();
            $table->timestamps();
            $table->unique(['lab_id', 'code']);
        });

        Schema::create('test_panel_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('test_definitions')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('test_definitions')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('required')->default(true);
            $table->unique(['panel_id', 'component_id']);
        });

        Schema::create('result_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_definition_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('label');
            $table->string('loinc')->nullable();
            $table->string('data_type', 20);
            $table->string('unit')->nullable();
            $table->unsignedTinyInteger('decimals')->default(0);
            $table->json('allowed_values')->nullable();
            $table->string('formula')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['test_definition_id', 'code']);
        });

        Schema::create('reference_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_definition_id')->constrained()->cascadeOnDelete();
            $table->string('sex', 10)->default('any');
            $table->unsignedSmallInteger('age_min_years')->nullable();
            $table->unsignedSmallInteger('age_max_years')->nullable();
            $table->decimal('lower_bound', 12, 4)->nullable();
            $table->decimal('upper_bound', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->string('source');
            $table->string('source_url')->nullable();
            $table->text('population_note')->nullable();
            $table->string('approval_status', 20)->default('approved');
            $table->timestamps();
        });

        Schema::create('critical_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_definition_id')->constrained()->cascadeOnDelete();
            $table->string('sex', 10)->default('any');
            $table->decimal('lower_bound', 12, 4)->nullable();
            $table->decimal('upper_bound', 12, 4)->nullable();
            $table->string('source');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('referring_doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('qualification')->nullable();
            $table->string('phone')->nullable();
            $table->string('registration_number')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->string('patient_number')->unique();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 10);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referring_doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('priority', 20)->default('routine');
            $table->string('status', 30)->default('ordered');
            $table->text('clinical_notes')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_definition_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('price_paise')->default(0);
            $table->timestamps();
        });

        Schema::create('specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specimen_type_id')->constrained()->restrictOnDelete();
            $table->string('barcode')->unique();
            $table->string('accession_number')->unique();
            $table->string('status', 30)->default('pending');
            $table->string('collection_site')->nullable();
            $table->string('condition')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('specimen_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specimen_id')->nullable()->constrained()->nullOnDelete();
            $table->text('value')->nullable();
            $table->decimal('numeric_value', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->string('flag', 10)->nullable();
            $table->text('comment')->nullable();
            $table->json('reference_snapshot')->nullable();
            $table->string('status', 30)->default('entered');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
            $table->unique(['order_item_id', 'result_definition_id']);
        });

        Schema::create('result_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('decision', 30);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('critical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('header_style', 30);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('report_template_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('report_template_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_number');
            $table->unsignedInteger('version');
            $table->unsignedInteger('template_version');
            $table->string('status', 30);
            $table->json('snapshot');
            $table->string('file_path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('verification_token', 80)->unique();
            $table->text('correction_reason')->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('report_versions')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['report_number', 'version']);
        });

        Schema::create('report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->unsignedInteger('amount_paise');
            $table->string('status', 20)->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('report_events');
        Schema::dropIfExists('report_versions');
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_template_id');
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_template_id');
        });
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('critical_alerts');
        Schema::dropIfExists('result_reviews');
        Schema::dropIfExists('results');
        Schema::dropIfExists('specimen_events');
        Schema::dropIfExists('specimens');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('referring_doctors');
        Schema::dropIfExists('critical_limits');
        Schema::dropIfExists('reference_intervals');
        Schema::dropIfExists('result_definitions');
        Schema::dropIfExists('test_panel_components');
        Schema::dropIfExists('test_definitions');
        Schema::dropIfExists('specimen_types');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('lab_id');
            $table->dropColumn(['role', 'designation', 'qualification']);
        });
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('labs');
    }
};
