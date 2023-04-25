<?php


use App\CentralLogics\helpers;
use App\Models\EMoney;
use App\Models\PaymentRecord;
use App\Models\Transaction;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (!function_exists('accept_withdraw_transaction')) {
    function accept_withdraw_transaction($receiver_user_id, $amount)
    {
        $admin_user_id = User::where('type', 0)->first()->id;

        DB::transaction(function () use ($admin_user_id, $receiver_user_id, $amount) {
            //RECEIVER TRANSACTION
            $account = EMoney::where('user_id', $receiver_user_id)->first();
            $account->pending_balance -= $amount;
            $account->save();

            $primary_transaction = Transaction::create([
                'user_id' => $receiver_user_id,
                'ref_trans_id' => null,
                'transaction_type' => WITHDRAW,
                'debit' => $amount,
                'credit' => 0,
                'balance' => $account->current_balance,
                'from_user_id' => $receiver_user_id,
                'to_user_id' => $admin_user_id,
                'note' => null,
                'transaction_id' => Str::random(5) . Carbon::now()->timestamp,
            ]);

            //ADMIN TRANSACTION
            $account = EMoney::where('user_id', $admin_user_id)->first();
            $account->current_balance += $amount;
            $account->save();

            Transaction::create([
                'user_id' => $admin_user_id,
                'ref_trans_id' => $primary_transaction['transaction_id'],
                'transaction_type' => WITHDRAW,
                'debit' => 0,
                'credit' => $amount,
                'balance' => $account->current_balance,
                'from_user_id' => $admin_user_id,
                'to_user_id' => $receiver_user_id,
                'note' => null,
                'transaction_id' => Str::random(5) . Carbon::now()->timestamp,
            ]);
        });
    }

    function payment_transaction($user, $merchant_user, $user_emoney, $merchant_emoney, $amount, $admin_user, $admin_emoney)
    {
        try {
            DB::beginTransaction();

            $admin_commission_percent = helpers::get_business_settings('merchant_commission_percent');
            $admin_amount = ($amount * $admin_commission_percent)/100;
            $merchant_amount = $amount - $admin_amount;

            $user_emoney->current_balance -= $amount;
            $user_emoney->current_balance;
            $user_emoney->save();

            $user_transaction = new Transaction();
            $user_transaction->user_id = $user->id;
            $user_transaction->ref_trans_id = null;
            $user_transaction->transaction_type = PAYMENT;
            $user_transaction->debit = $amount;
            $user_transaction->credit = 0;
            $user_transaction->balance = $user_emoney->current_balance;
            $user_transaction->from_user_id = $user->id;
            $user_transaction->to_user_id = $merchant_user->id;
            $user_transaction->note = null;
            $user_transaction->transaction_id = Str::random(5) . Carbon::now()->timestamp;
            $user_transaction->save();

            $merchant_emoney->current_balance += $merchant_amount;
            $merchant_emoney->current_balance;
            $merchant_emoney->save();

            $merchant_transaction = new Transaction();
            $merchant_transaction->user_id = $merchant_user->id;
            $merchant_transaction->ref_trans_id = $user_transaction['transaction_id'];
            $merchant_transaction->transaction_type = PAYMENT;
            $merchant_transaction->debit = 0;
            $merchant_transaction->credit = $merchant_amount;
            $merchant_transaction->balance = $merchant_emoney->current_balance;
            $merchant_transaction->from_user_id = $user->id;
            $merchant_transaction->to_user_id = $merchant_user->id;
            $merchant_transaction->note = null;
            $merchant_transaction->transaction_id = Str::random(5) . Carbon::now()->timestamp;
            $merchant_transaction->save();

            //$admin_emoney->current_balance += $admin_amount;
            //$admin_emoney->current_balance;
            $admin_emoney->charge_earned += $admin_amount;
            $admin_emoney->charge_earned;
            $admin_emoney->save();

            $admin_transaction = new Transaction();
            $admin_transaction->user_id = $admin_user->id;
            $admin_transaction->ref_trans_id = $user_transaction['transaction_id'];
            $admin_transaction->transaction_type = ADMIN_CHARGE;
            $admin_transaction->debit = 0;
            $admin_transaction->credit = $admin_amount;
            //$admin_transaction->balance = $admin_emoney->current_balance;
            $admin_transaction->balance = $admin_emoney->charge_earned;
            $admin_transaction->from_user_id = $user->id;
            $admin_transaction->to_user_id = $admin_user->id;
            $admin_transaction->note = null;
            $admin_transaction->transaction_id = Str::random(5) . Carbon::now()->timestamp;
            $admin_transaction->save();

            DB::commit();

            $config = null;
            $payment_message = \App\Models\BusinessSetting::where(['key' => 'payment'])->first();
            if (isset($payment_message)) {
                $config = json_decode($payment_message['value'], true);

                if ($config['status'] == 1){
                    $data = [
                        'title' => '',
                        'description' => Helpers::set_symbol($amount) . ' ' . $config['message'],
                        'image' => '',
                        'order_id'=>'',
                    ];
                    send_push_notification_to_device($user->fcm_token, $data);
                }
            }

            return $user_transaction['transaction_id'];

        }catch (Exception $exception){
            DB::rollBack();
        }

    }
}
