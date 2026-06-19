<?php

namespace App\Models;

use App\Support\ReceiptStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory;

    protected $appends = [
        'receipt_url',
    ];

    protected $fillable = [
        'user_id',
        'group_id',
        'category_id',
        'paid_by_user_id',
        'amount',
        'description',
        'expense_date',
        'notes',
        'receipt_path',
        'receipt_original_name',
        'is_recurring',
        'recurring_day',
        'recurrence_parent_id',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'is_recurring' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExpenseGroup::class, 'group_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurrence_parent_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ExpenseComment::class);
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return ReceiptStorage::url($this);
    }
}
