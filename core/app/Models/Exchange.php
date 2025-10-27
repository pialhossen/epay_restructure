<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\CommonScope;
use App\Traits\FileExport;
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    use CommonScope, FileExport;

    protected $guarded = ['id'];

    protected $casts = [
        'user_data' => 'object',
        'transaction_proof_data' => 'object',
        'charge' => 'object',
        'status_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sendCurrency()
    {
        return $this->belongsTo(Currency::class, 'send_currency_id');
    }

    public function receivedCurrency()
    {
        return $this->belongsTo(Currency::class, 'receive_currency_id');
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class);
    }

    public function scopeList($query)
    {
        return $query->whereIn('status', Status::EXCHANGE_ALL_STATUS);
    }

    public function scopeInitiated($query)
    {
        return $query->where('status', Status::EXCHANGE_INITIAL);
    }

    public function scopePending($query)
    {
        return $query->where('status', Status::EXCHANGE_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', Status::EXCHANGE_APPROVED);
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', Status::EXCHANGE_CANCEL);
    }

    public function scopeHold($query)
    {
        return $query->where('status', Status::EXCHANGE_HOLD);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', Status::EXCHANGE_PROCESSING);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', Status::EXCHANGE_REFUND);
    }

    public function badgeData($showTime = true)
    {
        $html = '';
        if ($this->status == Status::EXCHANGE_PENDING) {
            $html = '<span class="badge badge--warning">'.trans('Pending').'</span>';
        } elseif ($this->status == Status::EXCHANGE_APPROVED) {
            $html = '<span><span class="badge badge--success">'.trans('Approved').'</span>';
            if ($showTime) {
                $html .= '<br>'.diffForHumans($this->updated_at);
            }
            $html .= '</span>';
        } elseif ($this->status == Status::EXCHANGE_CANCEL) {
            $html = '<span class="badge badge--danger">'.trans('Canceled').'</span>';
        } elseif ($this->status == Status::EXCHANGE_HOLD) {
            $html = '<span class="badge badge--success">'.trans('Hold').'</span>';
        } elseif ($this->status == Status::EXCHANGE_PROCESSING) {
            $html = '<span class="badge badge--success">'.trans('Processing').'</span>';
        } elseif ($this->status == Status::EXCHANGE_REFUND) {
            $html = '<span><span class="badge badge--warning">'.trans('Refunded').'</span>';
            if ($showTime) {
                $html .= '<br>'.diffForHumans($this->updated_at);
            }
            $html .= '</span>';
        } elseif ($this->status == Status::EXCHANGE_INITIAL) {
            $html = '<span><span class="badge badge--primary">'.trans('Initiated').'</span>';
            if ($showTime) {
                $html .= '<br>'.diffForHumans($this->updated_at);
            }
            $html .= '</span>';
        }

        return $html;
    }
    public static function itemCount($status = Status::EXCHANGE_PENDING){
        return self::where('status', $status)->count();
    }
    public function updatedBy(){
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
