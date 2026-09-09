<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.3: authoritative append-only grant/revoke ledger (RULE-005).
// Rows are never updated or deleted — revokes are new rows. No updated_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granter_id')->constrained('users');
            $table->foreignId('grantee_id')->constrained('users');
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('permission_name');
            $table->string('action', 20); // granted|revoked (validated at service layer)
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('grantee_id');
            $table->index(['granter_id', 'grantee_id']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_grants');
    }
};
