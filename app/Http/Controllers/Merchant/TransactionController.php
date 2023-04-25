<?php

namespace App\Http\Controllers\Merchant;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function transaction(Request $request)
    {
        $trx_type = $request->has('trx_type') ? $request['trx_type'] : 'all';
        $query_param = [];
        $search = $request['search'];

        $key = explode(' ', $request['search']);

        $users = User::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%")
                    ->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            }
        })->get()->pluck('id')->toArray();

        $transactions = Transaction::
            when($request->has('search'), function ($q) use ($key, $users) {
                foreach ($key as $value) {
                    $q->orWhereIn('from_user_id', $users)
                        ->orWhereIn('to_user_id', $users)
                        ->orWhere('transaction_id', 'like', "%{$value}%")
                        ->orWhere('transaction_type', 'like', "%{$value}%");
                }
            })
            ->when($request['trx_type'] != 'all', function ($query) use ($request) {
                if ($request['trx_type'] == 'debit') {
                    return $query->where('debit', '!=', 0);
                } else {
                    return $query->where('credit', '!=', 0);
                }
            });

        $query_param = ['search' => $search, 'trx_type' => $trx_type];
        $transactions = $transactions->where('user_id', auth()->user()->id)->latest()->paginate(Helpers::pagination_limit())->appends($query_param);

        return view('merchant-views.transaction.list', compact( 'transactions', 'search', 'trx_type'));
    }
}
