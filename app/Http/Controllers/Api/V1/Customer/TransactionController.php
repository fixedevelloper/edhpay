<?php

namespace App\Http\Controllers\Api\V1\Customer;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
     * @param Request $request
     * @return JsonResponse
     */
    public function send_money(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|min:4|max:4',
            'phone' => 'required',
            'purpose' => '',
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
        $customer_transaction = $this->customer_send_money_transaction($request->user()->id, Helpers::get_user_id($receiver_phone), $request['amount']);

        if (is_null($customer_transaction)) return response()->json(['message' => translate('fail')], 501); //if failed

        return response()->json(['message' => 'success', 'transaction_id' => $customer_transaction], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function cash_out(Request $request): JsonResponse
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
            return response()->json(['message' => translate('Verify your account information')], 403); //kyc check

        if ($request->user()->phone == $receiver_phone)
            return response()->json(['message' => translate('Transaction should not with own number')], 400); //own number check

        if($user->type != 1)
            return response()->json(['message' => translate('Receiver must be an agent')], 400); //'if receiver is customer' check

        if (!Helpers::pin_check($request->user()->id, $request->pin))
            return response()->json(['message' => translate('PIN is incorrect')], 403); //PIN Check


        /** Transaction */
        $customer_transaction = $this->customer_cash_out_transaction($request->user()->id, Helpers::get_user_id($receiver_phone), $request['amount']);

        if (is_null($customer_transaction)) return response()->json(['message' => translate('fail')], 501); //if failed

        return response()->json(['message' => 'success', 'transaction_id' => $customer_transaction], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function request_money(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'amount' => 'required|min:0|not_in:0',
            'note' => '',
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
            return response()->json(['message' => translate('Verify your account information')], 403); //kyc check

        if ($request->user()->phone == $receiver_phone)
            return response()->json(['message' => translate('Transaction should not with own number')], 400); //own number check

        if($user->type !=  2)
            return response()->json(['message' => translate('Receiver must be a user')], 400); //'if receiver is customer' check


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
     * @param Request $request
     * @param $slug
     * @return JsonResponse
     * @throws \Exception
     */
    public function request_money_status(Request $request, $slug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|min:4|max:4',
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!in_array(strtolower($slug), ['deny', 'approve'])) {
            return response()->json(['message' => translate('Invalid request')], 403);
        }

        $request_money = RequestMoney::find($request->id);

        /** Transaction validation check */
        if (!isset($request_money))
            return response()->json(['message' => translate('Request not found')], 404);

        if(User::find($request_money->to_user_id)->is_kyc_verified != 1)
            return response()->json(['message' => 'Receiver is not verified'], 403); //kyc check

        if($request->user()->is_kyc_verified != 1)
            return response()->json(['message' => 'Complete your account verification'], 403); //kyc check

        if($request_money->to_user_id != $request->user()->id)
            return response()->json(['message' => 'unauthorized request'], 403); //access check

        if (!Helpers::pin_check($request->user()->id, $request->pin))
            return response()->json(['message' => 'PIN is incorrect'], 403); //PIN Check

        //if deny
        if (strtolower($slug) == 'deny') {
            $request_money->type = 'denied';
            $request_money->note = $request->note;
            $request_money->save();

            //send notification
            Helpers::send_transaction_notification($request_money->from_user_id, $request_money->amount, 'denied_money');
            Helpers::send_transaction_notification($request_money->to_user_id, $request_money->amount, 'denied_money');

            return response()->json(['message' => 'success'], 200);
        }

        //if approved
        /** Transaction */
        $customer_transaction = $this->customer_request_money_transaction($request_money->to_user_id, $request_money->from_user_id, $request_money->amount);

        if (is_null($customer_transaction)) return response()->json(['message' => translate('fail')], 501); //if failed

        //update request money status
        $request_money->type = 'approved';
        $request_money->note = $request->note;
        $request_money->save();
        return response()->json(['message' => 'success', 'transaction_id' => $customer_transaction], 200); //if success
    }

    /**
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

        if($request->user()->is_kyc_verified != 1) {
            return response()->json(['message' => translate('Verify your account information')], 403); //kyc check
        }

        //emoney check
        $amount = $request->amount;
        $bonus = Helpers::get_add_money_bonus($amount, $request->user()->id, 'customer');
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
//            ->select('transaction_type', 'credit', 'debit', 'created_at', DB::raw("(debit + credit) as amount"))
//            ->select('transaction_type', 'credit', 'debit', 'created_at')
            ->customer()
            ->where('transaction_type', '!=', ADMIN_CHARGE)
            ->where('transaction_type', '!=', 'agent_commission')
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
     * @param Request $request
     * @return JsonResponse
     */
    public function withdrawal_methods(Request $request): JsonResponse
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


        $user_emoney = EMoney::where('user_id', $request->user()->id)->first();
        if ($user_emoney->current_balance < $total_amount) {
            return response()->json(['message' => translate('Your account do not have enough balance')], 403);
        }

        $user_emoney->current_balance -= $total_amount;
        $user_emoney->pending_balance += $total_amount;

        DB::transaction(function () use ($withdraw_request, $user_emoney) {
            $withdraw_request->save();
            $user_emoney->save();
        });

        return response()->json(response_formatter(DEFAULT_STORE_200, null, null), 200);
        //return response()->json(['message' => translate('Withdraw request failed')], 403); //for failed
    }
}
