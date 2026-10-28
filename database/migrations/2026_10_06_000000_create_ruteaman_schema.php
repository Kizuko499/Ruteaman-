<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        $pgsql=DB::getDriverName()==='pgsql';
        $srid=\App\Services\SpatialSql::isMariaDb()?'':' SRID 4326';
        if($pgsql)DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('schools', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('bmkg_area_code')->nullable(); $table->string('timezone')->default('Asia/Jakarta');
        });
        if($pgsql){
            DB::statement('ALTER TABLE schools ADD COLUMN location geometry(Point,4326) NOT NULL');
            DB::statement('ALTER TABLE schools ADD COLUMN catchment geometry(Polygon,4326) NOT NULL');
            DB::statement('CREATE INDEX schools_location_gist ON schools USING GIST (location)');
            DB::statement('CREATE INDEX schools_catchment_gist ON schools USING GIST (catchment)');
        }else{
            DB::statement("ALTER TABLE schools ADD COLUMN location POINT NOT NULL$srid");
            DB::statement("ALTER TABLE schools ADD COLUMN catchment POLYGON NOT NULL$srid");
            DB::statement('CREATE SPATIAL INDEX schools_location_spatial ON schools (location)');
            DB::statement('CREATE SPATIAL INDEX schools_catchment_spatial ON schools (catchment)');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('student'); $table->string('display_name',100); $table->string('email')->unique();
            $table->string('password'); $table->timestamp('parental_consent_at')->nullable(); $table->boolean('active')->default(true);
            $table->rememberToken(); $table->timestamp('email_verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('hazard_types', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('label'); $table->unsignedTinyInteger('severity_weight');
            $table->unsignedInteger('ttl_minutes'); $table->boolean('rain_sensitive')->default(false); $table->boolean('night_only')->default(false);
        });
        Schema::create('hazards', function (Blueprint $table) {
            $table->id(); $table->foreignId('type_id')->constrained('hazard_types'); $table->unsignedTinyInteger('severity');
            $table->string('status')->default('new')->index(); $table->decimal('confidence',5,4)->default(0);
            $table->timestamp('first_reported_at'); $table->timestamp('last_confirmed_at')->nullable()->index(); $table->timestamp('resolved_at')->nullable();
            $table->string('public_slug',40)->unique(); $table->timestamps();
        });
        if($pgsql){
            DB::statement('ALTER TABLE hazards ADD COLUMN location geometry(Point,4326) NOT NULL');
            DB::statement('CREATE INDEX hazards_location_gist ON hazards USING GIST (location)');
        }else{
            DB::statement("ALTER TABLE hazards ADD COLUMN location POINT NOT NULL$srid");
            DB::statement('CREATE SPATIAL INDEX hazards_location_spatial ON hazards (location)');
        }

        Schema::create('hazard_reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('hazard_id')->constrained()->cascadeOnDelete(); $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable(); $table->string('photo_path')->nullable(); $table->timestamp('observed_at'); $table->timestamps();
        });
        Schema::create('hazard_confirmations', function (Blueprint $table) {
            $table->id(); $table->foreignId('hazard_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vote'); $table->timestamp('created_at'); $table->unique(['hazard_id','user_id']);
        });
        Schema::create('verifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('hazard_id')->constrained()->cascadeOnDelete(); $table->foreignId('verifier_id')->constrained('users');
            $table->string('decision'); $table->text('note')->nullable(); $table->timestamp('created_at');
        });
        Schema::create('escalations', function (Blueprint $table) {
            $table->id(); $table->foreignId('hazard_id')->constrained()->cascadeOnDelete(); $table->string('channel');
            $table->timestamp('forwarded_at')->nullable(); $table->string('reference_id')->nullable(); $table->string('response_status')->default('draft');
            $table->text('response_note')->nullable(); $table->timestamp('response_at')->nullable();
        });
        Schema::create('weather_snapshots', function (Blueprint $table) {
            $table->id(); $table->string('area_code')->index(); $table->string('condition'); $table->boolean('rain_flag');
            $table->string('source'); $table->timestamp('fetched_at'); $table->timestamp('valid_until');
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('action');
            $table->string('entity'); $table->unsignedBigInteger('entity_id')->nullable(); $table->json('meta_json')->nullable(); $table->timestamp('created_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) { $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable(); });
        Schema::create('jobs', function (Blueprint $table) { $table->bigIncrements('id'); $table->string('queue')->index(); $table->longText('payload'); $table->unsignedTinyInteger('attempts'); $table->unsignedInteger('reserved_at')->nullable(); $table->unsignedInteger('available_at'); $table->unsignedInteger('created_at'); });
        Schema::create('job_batches', function (Blueprint $table) { $table->string('id')->primary(); $table->string('name'); $table->integer('total_jobs'); $table->integer('pending_jobs'); $table->integer('failed_jobs'); $table->longText('failed_job_ids'); $table->mediumText('options')->nullable(); $table->integer('cancelled_at')->nullable(); $table->integer('created_at'); $table->integer('finished_at')->nullable(); });
        Schema::create('failed_jobs', function (Blueprint $table) { $table->id(); $table->string('uuid')->unique(); $table->text('connection'); $table->text('queue'); $table->longText('payload'); $table->longText('exception'); $table->timestamp('failed_at')->useCurrent(); });
    }

    public function down(): void
    {
        foreach (['failed_jobs','job_batches','jobs','password_reset_tokens','audit_logs','weather_snapshots','escalations','verifications','hazard_confirmations','hazard_reports','hazards','hazard_types','users','schools'] as $table) Schema::dropIfExists($table);
    }
};