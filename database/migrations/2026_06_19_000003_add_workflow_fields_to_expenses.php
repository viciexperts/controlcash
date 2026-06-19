<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('notes');
            $table->string('receipt_original_name')->nullable()->after('receipt_path');
            $table->boolean('is_recurring')->default(false)->after('receipt_original_name');
            $table->unsignedTinyInteger('recurring_day')->nullable()->after('is_recurring');
            $table->foreignId('recurrence_parent_id')->nullable()->after('recurring_day')->constrained('expenses')->nullOnDelete();
            $table->string('approval_status')->default('approved')->after('recurrence_parent_id');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->index(['approval_status', 'expense_date']);
            $table->index('recurrence_parent_id');
        });

        Schema::create('expense_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_comments');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status', 'expense_date']);
            $table->dropIndex(['recurrence_parent_id']);
            $table->dropColumn([
                'receipt_path',
                'receipt_original_name',
                'is_recurring',
                'recurring_day',
                'recurrence_parent_id',
                'approval_status',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
