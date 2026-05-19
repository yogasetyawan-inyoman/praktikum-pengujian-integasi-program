<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveRequest extends Model
{
    //
    use HasFactory;
    protected $table = 'leave_requests';
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'days',
        'type',
        'reason',
        'attachment',
        'status',
        'approved_by',
        'approved_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
