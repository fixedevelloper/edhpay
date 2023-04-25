<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class WithdrawController extends Controller
{
    private $withdraw_request;
    private $withdrawal_method;
    private $user;

    public function __construct(WithdrawRequest $withdraw_request, WithdrawalMethod $withdrawal_method, User $user)
    {
        $this->withdraw_request = $withdraw_request;
        $this->withdrawal_method = $withdrawal_method;
        $this->user = $user;
    }

    public function list(Request $request)
    {
        $withdraw_requests = $this->withdraw_request->with('user', 'withdrawal_method')
            ->where(['user_id' => auth()->user()->id])
            ->latest()
            ->get();

        return response()->json(response_formatter(DEFAULT_200, $withdraw_requests, null), 200);
    }
}
