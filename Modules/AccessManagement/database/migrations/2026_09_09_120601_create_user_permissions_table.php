<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ADR-012: O(1) authorization read model. permission_grants stays the
// append-only audit ledger and source of truth for history (RULE-005);
// user_permissions mirrors CURRENT effective grants only, maintained
// atomically by the grant/revoke service (Phase 5 wiring).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission_name');
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('granted_via_grant_id')->constrained('permission_grants')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_name', 'group_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
