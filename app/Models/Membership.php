<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;
    protected $table = "memberships";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'start_date',
        'end_date',	
        'pay',
        'balance',	
        'state',
    ];

    public function member(): BelongsTo {
        return $this->belongsTo(Member::class);
    }
}
