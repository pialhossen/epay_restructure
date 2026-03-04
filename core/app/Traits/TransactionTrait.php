<?php

namespace App\Traits;

use App\Models\Exchange;
use App\Models\TransactionSerial;

trait TransactionTrait
{
    public function getTransactionSerial($transaction_type)
    {
        $exchange_id = '';
        while(1){
            $prefixes = [
                'DEPOSIT' => 'DP',
                'WITHDRAW' => 'WD',
                'EXCHANGE' => 'EX',
            ];
    
            if (!isset($prefixes[$transaction_type])) {
                return null; // Or throw exception if invalid
            }
    
            // Get or create TransactionSerial
            $transactionSerial = TransactionSerial::firstOrCreate(
                ['transaction_type' => $transaction_type],
                ['serial_no' => 0]
            );
    
            // Increment and save
            $serialNo = $transactionSerial->serial_no + 1;
            $transactionSerial->update(['serial_no' => $serialNo]);
    
            $exchange_id = $prefixes[$transaction_type] . '-' . $serialNo;
            if(!Exchange::where('exchange_id',$exchange_id)->exists()){
                break;
            }
        }

        return $prefixes[$transaction_type] . '-' . $serialNo;
    }

}
