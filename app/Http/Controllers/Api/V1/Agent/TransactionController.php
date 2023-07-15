<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\CentralLogics\helpers;
use App\Exceptions\TransactionFailedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\EMoney;
use App\Models\RequestMoney;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use App\Traits\TransactionTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    private $withdrawal_method;
    private $withdraw_request;

    use TransactionTrait;

    public function __construct(WithdrawalMethod $withdrawal_method, WithdrawRequest $withdraw_request)
    {
        $this->withdrawal_method = $withdrawal_method;
        $this->withdraw_request = $withdraw_request;
    }


    /**
     * CASH IN or send money
     * @param Request $request
     * @return JsonResponse
     */
    public function cash_in(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|min:4|max:4',
            'phone' => 'required',
            'amount' => 'required|min:0|not_in:0',
        ],
            [
                'amount.not_in' => translate('Amount must be greater than zero!'),
            ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $receiver_phone = Helpers::filter_phone($request->phone);
        $user = User::where('phone', $receiver_phone)->first();

        /** Transaction validation check */
        if (!isset($user))
            return response()->json(['message' => translate('Receiver not found')], 403); //Receiver Check

        if($user->is_kyc_verified != 1)
            return response()->json(['message' => translate('Receiver is not verified')], 403); //kyc check

        if($request->user()->is_kyc_verified != 1)
            return response()->json(['message' => translate('Complete your account verification')], 403); //kyc check

        if ($request->user()->phone == $receiver_phone)
            return response()->json(['message' => translate('Transaction should not with own number')], 400); //own number check

        if($user->type != 2)
            return response()->json(['message' => translate('Receiver must be a user')], 400); //'if receiver is customer' check

        if (!Helpers::pin_check($request->user()->id, $request->pin))
            return response()->json(['message' => translate('PIN is incorrect')], 403); //PIN Check


        /** Transaction */
        $customer_transaction = $this->cash_in_transaction($request->user()->id, Helpers::get_user_id($receiver_phone), $request['amount']);

        if (is_null($customer_transaction)) return response()->json(['message' => translate('failed')], 501); //if failed
        return response()->json(['message' => 'success', 'transaction_id' => $customer_transaction], 200); //if success
    }

    /**
     * Request money to admin
     * @param Request $request
     * @return JsonResponse
     */
    public function request_money(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|min:0|not_in:0',
            'note' => '',
        ],
            [
                'amount.not_in' => translate('Amount must be greater than zero!'),
            ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = User::where('type', 0)->first();
        $receiver_phone = $user->phone;

        /** Transaction validation check */
        if (!isset($user))
            return response()->json(['message' => 'Receiver not found'], 403); //Receiver Check

        if($request->user()->is_kyc_verified != 1)
            return response()->json(['message' => 'Complete your account verification'], 403); //kyc check

        if ($request->user()->phone == $receiver_phone)
            return response()->json(['message' => 'Transaction should not with own number'], 400); //own number check

        if($user->type !=  ADMIN_TYPE)
            return response()->json(['message' => 'Receiver must be an admin'], 400); //'if receiver is admin' check

        /** request_money db operation */
        $request_money = new RequestMoney();
        $request_money->from_user_id = $request->user()->id;
        $request_money->to_user_id = Helpers::get_user_id($receiver_phone);
        $request_money->type = 'pending';
        $request_money->amount = $request->amount;
        $request_money->note = $request->note;
        $request_money->save();

        //send notification
        Helpers::send_transaction_notification($request_money->from_user_id, $request->amount, 'request_money');
        Helpers::send_transaction_notification($request_money->to_user_id, $request->amount, 'request_money');

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * add money from bank
     * @param Request $request
     * @return JsonResponse
     */
    public function add_money(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        //kyc check
        if($request->user()->is_kyc_verified != 1) {
            return response()->json(['message' => 'Complete your account verification'], 403);
        }

        //emoney check
        $amount = $request->amount;
        $bonus = Helpers::get_add_money_bonus($amount, $request->user()->id, 'agent');
        $total_amount = $amount + $bonus;

        $admin_emoney = EMoney::where('user_id', Helpers::get_admin_id())->first();
        if($admin_emoney && $total_amount > $admin_emoney->current_balance) {
            return response()->json(['message' => translate('The amount is too big. Please contact with admin')], 403);
        }

        $user_id = $request->user()->id;
        $amount = $request->amount;
        $link = route('payment-mobile', ['user_id' => $user_id, 'amount' => $amount]);
        return response()->json(['link' => $link], 200);
    }

    /**
     * filtered transaction history
     * @param Request $request
     * @return array
     */
    public function transaction_history(Request $request): array
    {
        $limit = $request->has('limit') ? $request->limit : 10;
        $offset = $request->has('offset') ? $request->offset : 1;

        $transactions = Transaction::where('user_id', $request->user()->id);

        $transactions->when(request('transaction_type') == CASH_IN, function ($q) {
            return $q->where('transaction_type', CASH_IN);
        });
        $transactions->when(request('transaction_type') == CASH_OUT, function ($q) {
            return $q->where('transaction_type', CASH_OUT);
        });
        $transactions->when(request('transaction_type') == SEND_MONEY, function ($q) {
            return $q->where('transaction_type', SEND_MONEY);
        });
        $transactions->when(request('transaction_type') == RECEIVED_MONEY, function ($q) {
            return $q->where('transaction_type', RECEIVED_MONEY);
        });
        $transactions->when(request('transaction_type') == ADD_MONEY, function ($q) {
            return $q->where('transaction_type', ADD_MONEY);
        });

        $transactions = $transactions
            ->agent()
            ->where('transaction_type', '!=', ADMIN_CHARGE)
            ->orderBy("created_at", 'desc')
            ->paginate($limit, ['*'], 'page', $offset);

        $transactions = TransactionResource::collection($transactions);

        return [
            'total_size' => $transactions->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'transactions' => $transactions->items()
        ];
    }

    /**
     * @return JsonResponse
     */
    public function withdrawal_methods(): JsonResponse
    {
        $withdrawal_methods = $this->withdrawal_method->latest()->get();
        return response()->json(response_formatter(DEFAULT_200, $withdrawal_methods, null), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|min:4|max:4',
            'amount' => 'required|min:0|not_in:0',
            'note' => 'max:255',
            'withdrawal_method_id' => 'required',
            'withdrawal_method_fields' => 'required',
        ],
            [
                'amount.not_in' => translate('Amount must be greater than zero!'),
            ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if($request->user()->is_kyc_verified != 1) {
            return response()->json(['message' => translate('Your account is not verified, Complete your account verification')], 403);
        }

        //input fields validation check
        $withdrawal_method = $this->withdrawal_method->find($request->withdrawal_method_id);
        $fields = array_column($withdrawal_method->method_fields, 'input_name');

        $values = (array)json_decode(base64_decode($request->withdrawal_method_fields))[0];

        foreach ($fields as $field) {
            if(!key_exists($field, $values)) {
                return response()->json(response_formatter(DEFAULT_400, $fields, null), 400);
            }
        }

        $amount = $request->amount;
        $charge = helpers::get_withdraw_charge($amount);
        $total_amount = $amount + $charge;

        /** DB Operations */
        $withdraw_request = $this->withdraw_request;
        $withdraw_request->user_id = $request->user()->id;
        $withdraw_request->amount = $amount;
        $withdraw_request->admin_charge = $charge;
        $withdraw_request->request_status = 'pending';
        $withdraw_request->is_paid = 0;
        $withdraw_request->sender_note = $request->sender_note;
        $withdraw_request->withdrawal_method_id = $request->withdrawal_method_id;
        $withdraw_request->withdrawal_method_fields = $values;


        $agent_emoney = EMoney::where('user_id', $request->user()->id)->first();
        if ($agent_emoney->current_balance < $total_amount) {
            return response()->json(['message' => translate('Your account do not have enough balance')], 403);
        }
        /** To user's(agent commission) credit */
        $agent_commission = Helpers::get_agent_commission($charge);
        $emoney = EMoney::where('user_id', $request->user()->id)->first();
        $emoney->current_balance += $agent_commission;
        $emoney->save();

        Transaction::create([
            'user_id' => $request->user()->id,
            'ref_trans_id' => null,
            'transaction_type' => AGENT_COMMISSION,
            'debit' => 0,
            'credit' => $agent_commission,
            'balance' => $emoney->current_balance,
            'from_user_id' => $request->user()->id,
            'to_user_id' => $request->user()->id,
            'note' => "Note agent",
            'transaction_id' => Str::random(5) . Carbon::now()->timestamp,
        ]);
        //end commission

        $agent_emoney->current_balance -= $total_amount;
        $agent_emoney->pending_balance += $total_amount;

        DB::transaction(function () use ($withdraw_request, $agent_emoney) {
            $withdraw_request->save();
            $agent_emoney->save();
        });

        return response()->json(response_formatter(DEFAULT_STORE_200, null, null), 200);
    }
}
