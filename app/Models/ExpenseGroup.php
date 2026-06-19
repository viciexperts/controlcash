<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseGroup extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'created_by',
        'name',
        'description',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'group_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'group_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'group_id');
    }
}
