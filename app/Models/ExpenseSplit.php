<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ExpenseSplit extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'expense_splits';

    protected $fillable = [
        'expense_id',
        'user_id',
        'amount',
        'percent',
    ];

    protected $casts = [
        'amount' => 'float',
        'percent' => 'float',
    ];
}
