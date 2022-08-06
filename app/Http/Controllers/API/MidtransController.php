<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Midtrans\Config;

class MidtransController extends Controller
{
    public function callback(){
        // Set Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Buat instance midtrans notification
        $notification = new Notification();

        // Assign ke variable u/ memudahkan coding
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Get transaciton id
        $order = explode('-', $order_id);

        // Cari transaksi berdasarkan ID
        $transaction = Transaction::findOrfield($order[1]);

        // Handle notification status midtrans
        if($status == 'capture'){
            if($type == 'credit_card'){
                if($fraud == 'challenge'){
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }
            }
        }

        else if($status == 'settlement'){
            $transaction->success = 'SUCCESS';
        }
        else if($status == 'pending'){
            $transaction->success = 'PENDING';
        }
        else if($status == 'deny'){
            $transaction->success = 'PENDING';
        }
        else if($status == 'expire'){
            $transaction->success = 'CANCELLED';
        }
        else if($status == 'cancel'){
            $transaction->success = 'CANCELLED';
        }

        // Simpan transaksi
        $transaction->save();

        // Return response u/ midtrans
        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Notification Success!'
            ]
        ]);
    }
}
