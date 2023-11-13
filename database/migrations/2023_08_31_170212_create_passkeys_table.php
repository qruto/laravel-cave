<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users');
            $table->string('name')->nullable();
            $table->text('credential_id');
            $table->text('credential_public_key');
            $table->json('transports');
            $table->unsignedBigInteger('counter');
            $table->string('attestation_type')->default('none');
            $table->text('attestation_trust_path');
            $table->uuid('attestation_aaguid');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_keys');
    }
};
