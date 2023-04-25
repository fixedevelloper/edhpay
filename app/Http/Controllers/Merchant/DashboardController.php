<?php

namespace App\Http\Controllers\Merchant;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Models\EMoney;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $balance = self::get_balance_stat();

        $query_param = [];
        $withdraw_requests = WithdrawRequest::with('withdrawal_method')
            ->when($request->has('withdrawal_method') && $request->withdrawal_method != 'all', function ($query) use ($request) {
                return $query->where('withdrawal_method_id', $request->withdrawal_method);
            })
            ->where('user_id', auth()->user()->id)
            ->latest()
            ->paginate(Helpers::pagination_limit())
            ->appends($query_param);

        $method = $request->withdrawal_method;
        $withdrawal_methods = WithdrawalMethod::latest()->get();

        return view('merchant-views.dashboard', compact('balance', 'withdraw_requests', 'withdrawal_methods', 'method'));
    }

    public function get_balance_stat()
    {
        $current_balance = EMoney::where('user_id', auth()->user()->id)->sum('current_balance');
        $pending_balance = EMoney::where('user_id', auth()->user()->id)->sum('pending_balance');
        $total_withdraw = WithdrawRequest::where('user_id', auth()->user()->id)
            ->where(['is_paid' => 1, 'request_status' => 'approved'])
            ->sum('amount');

        $balance = [];
        $balance['current_balance'] = $current_balance;
        $balance['pending_balance'] = $pending_balance;
        $balance['total_withdraw'] = $total_withdraw;

        return $balance;
    }

    public function settings()
    {
        return view('merchant-views.settings');
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
        ]);

        $merchant = User::find(auth('user')->id());
        $merchant->f_name = $request->f_name;
        $merchant->l_name = $request->l_name;
        $merchant->email = $request->email;
        //$merchant->phone = $request->phone;
        $merchant->image = $request->has('image') ? Helpers::update('merchant/', $merchant->image, 'png', $request->file('image')) : $merchant->image;
        $merchant->save();
        Toastr::success('Merchant updated successfully!');
        return back();
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => 'required|same:confirm_password|max:6|min:6',
            'confirm_password' => 'required',
        ]);
        $merchant = User::find(auth('user')->id());
        $merchant->password = bcrypt($request['password']);
        $merchant->save();
        Toastr::success('Merchant password updated successfully!');
        return back();
    }
}
