<?php

namespace App\Observers;

use App\Models\Currency;
use App\Models\CurrencyReservedLog;

class CurrencyObserver
{
    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        if ($currency->wasChanged('reserve')) {
            $business_day = now()->format('Ymd');

            CurrencyReservedLog::updateOrCreate(
                [
                    'currency_id' => $currency->id,
                    'currency_name' => $currency->name,
                    'business_day'    => $business_day, // unique per day per currency
                ],
                [
                    'reserved' => $currency->reserve,
                ]
            );
        }
    }

    /**
     * Handle the Currency "deleted" event.
     */
    public function deleted(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "restored" event.
     */
    public function restored(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "force deleted" event.
     */
    public function forceDeleted(Currency $currency): void
    {
        //
    }
}
