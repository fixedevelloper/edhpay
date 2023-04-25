<?php

namespace App\Http\Controllers\Merchant;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\EMoney;
use App\Models\User;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class WithdrawController extends Controller
{
    private $withdraw_request;
    private $withdrawal_method;

    public function __construct(WithdrawRequest $withdraw_request, WithdrawalMethod $withdrawal_method)
    {
        $this->withdraw_request = $withdraw_request;
        $this->withdrawal_method = $withdrawal_method;
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        $request_status = $request['request_status'];

        $method = $request->withdrawal_method;
        $withdraw_requests = $this->withdraw_request->with('user', 'withdrawal_method')
            ->when($request->has('search'), function ($query) use ($request) {
                $key = explode(' ', $request['search']);

                $user_ids = User::where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    }
                })->get()->pluck('id')->toArray();

               // $query_param = ['search' => $request['search']];
                return $query->whereIn('user_id', $user_ids);
            })
            ->when($request->has('request_status') && $request->request_status != 'all', function ($query) use ($request) {
                return $query->where('request_status', $request->request_status);
            })
            ->when($request->has('withdrawal_method') && $request->withdrawal_method != 'all', function ($query) use ($request) {
                return $query->where('withdrawal_method_id', $request->withdrawal_method);
            });

        $query_param = ['search' => $request['search'], 'request_status' => $request['request_status']];
        $withdraw_requests = $withdraw_requests->where('user_id', auth()->user()->id)->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        $withdrawal_methods = $this->withdrawal_method->latest()->get();

        return view('merchant-views.withdraw.list', compact('withdraw_requests', 'withdrawal_methods', 'method', 'search', 'request_status'));
    }

    public function withdraw_request()
    {
        $withdrawal_methods = WithdrawalMethod::latest()->get();
        $merchant_emoney = EMoney::where(['user_id' => auth()->user()->id])->first();
        $maximum_amount = $merchant_emoney->current_balance;
        return view('merchant-views.withdraw.index', compact('withdrawal_methods', 'maximum_amount'));
    }

    public function withdraw_request_store(Request $request)
    {
        $request->validate([
            'amount' => 'required|min:0|not_in:0',
            'note' => 'max:255',
            'withdrawal_method_id' => 'required',
        ],[
            'amount.not_in' => translate('Amount must be greater than zero!'),
        ]);

        $withdrawal_method = $this->withdrawal_method->find($request->withdrawal_method_id);
        $fields = array_column($withdrawal_method->method_fields, 'input_name');
        $values = $request->all();
        $data = [];

        foreach ($fields as $field) {
            if(key_exists($field, $values)) {
                $data[$field] = $values[$field];
            }
        }

        try {
            DB::beginTransaction();

            $withdraw_request = new WithdrawRequest();
            $withdraw_request->user_id = auth()->user()->id;
            $withdraw_request->amount =$request->amount;
            $withdraw_request->request_status = 'pending';
            $withdraw_request->is_paid = 0;
            $withdraw_request->sender_note = $request->sender_note;
            $withdraw_request->withdrawal_method_id = $request->withdrawal_method_id;
            $withdraw_request->withdrawal_method_fields = $data;
            $withdraw_request->save();

            $merchant_emoney = EMoney::where('user_id', auth()->user()->id)->first();

            if ($merchant_emoney->current_balance < $request->amount) {
                Toastr::warning(translate('Your account do not have enough balance.'));
                return back();
            }

            $merchant_emoney->current_balance -= $request->amount;
            $merchant_emoney->pending_balance += $request->amount;
            $merchant_emoney->save();

            DB::commit();

            Toastr::success(translate('Withdraw request send !'));
            return redirect()->route('merchant.withdraw.list');
        } catch (\Exception $e) {
            DB::rollback();
            Toastr::warning(translate('Withdraw request send failed!'));
            return back();
        }




    }

    public function withdraw_method(Request $request)
    {
        $method = $this->withdrawal_method->where(['id' => $request->withdrawal_method_id])->first();
        return response()->json($method, 200);
    }

    public function download(Request $request)
    {
        $withdraw_requests = $this->withdraw_request
            ->when($request->has('search'), function ($query) use ($request) {
                $key = explode(' ', $request['search']);

                $user_ids = User::where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    }
                })->get()->pluck('id')->toArray();

                return $query->whereIn('user_id', $user_ids);
            })
            ->with('user', 'withdrawal_method')
            ->when($request->has('withdrawal_method') && $request->withdrawal_method != 'all', function ($query) use ($request) {
                return $query->where('withdrawal_method_id', $request->withdrawal_method);
            })
            ->where('user_id', auth()->user()->id)
            ->get();

        $storage = [];

        foreach ($withdraw_requests as $key=>$withdraw_request) {
            if (!is_null($withdraw_request->user) && !is_null($withdraw_request->withdrawal_method_fields)) {
                $data = [
                    'No' => $key+1,
                    'UserName' => $withdraw_request->user->f_name . ' ' . $withdraw_request->user->l_name,
                    'UserPhone' => $withdraw_request->user->phone,
                    'UserEmail' => $withdraw_request->user->email,
                    'MethodName' => $withdraw_request->withdrawal_method->method_name??'',
                    'Amount' => $withdraw_request->amount,
                    'RequestStatus' => $withdraw_request->request_status,
                ];

                $storage[] = array_merge($data, $withdraw_request->withdrawal_method_fields);
            }
        }

        return (new FastExcel($storage))->download(time() . '-file.xlsx');
    }
}
