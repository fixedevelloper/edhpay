<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\EMoney;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogHistory;
use Brian2694\Toastr\Facades\Toastr;
use http\Env;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stevebauman\Location\Facades\Location;

class CustomerController extends Controller
{
    protected $user;
    protected $user_log_history;

    public function __construct(User $user, UserLogHistory $user_log_history)
    {
        $this->user = $user;
        $this->user_log_history = $user_log_history;
    }


    public function index(Request $request)
    {
        $ip = env('APP_MODE') == 'live' ? $request->ip() : '61.247.180.82';
        $current_user_info = Location::get($ip);
        return view('admin-views.customer.index', compact('current_user_info'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'image' => 'required',
            'country_code' => 'required',
            'phone' => 'required|unique:users|min:5|max:20',
            'gender' => 'required',
            'occupation' => 'required',
            'password' => 'required|min:4|max:4',
        ], [
            'password.min' => 'Password must contain 4 characters',
            'password.max' => 'Password must contain 4 characters',
        ]);

        $phone = $request->country_code . $request->phone;
        $customer = User::where(['phone' => $phone])->first();
        if (isset($customer)){
            Toastr::warning(translate('This phone number is already taken'));
            return back();
        }

        DB::transaction(function () use ($request, $phone) {
            $user = new User();
            $user->f_name = $request->f_name;
            $user->l_name = $request->l_name;
            $user->image = Helpers::upload('customer/', 'png', $request->file('image'));
            $user->email = $request->email;
            $user->dial_country_code = $request->country_code;
            $user->phone = $phone;
            $user->gender = $request->gender;
            $user->occupation = $request->occupation;
            $user->password = bcrypt($request->password);
            $user->type = CUSTOMER_TYPE;    //['Admin'=>0, 'Agent'=>1, 'Customer'=>2]
            $user->referral_id = $request->referral_id ?? null;
            $user->save();

            $user->find($user->id);
            $user->unique_id = $user->id . mt_rand(1111, 99999);
            $user->save();

            $emoney = new EMoney();
            $emoney->user_id = $user->id;
            $emoney->save();
        });

        Toastr::success(translate('Customer Added Successfully!'));
        return back();
    }

    public function customer_list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $customers = User::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $customers = new User();
        }

        $customers = $customers->latest()->customer()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.customer.list', compact('customers', 'search'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $customers = User::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('admin-views.customer.partials._table', compact('customers'))->render(),
        ]);
    }

    public function view($id)
    {
        $user = User::with('emoney')->find($id);
        return view('admin-views.view.details', compact('user'));
    }

    public function transaction(Request $request, $id)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
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

            $transactions = Transaction::where(function ($q) use ($key, $users) {
                foreach ($key as $value) {
                    $q->orWhereIn('from_user_id', $users)
                        ->orWhere('to_user_id', $users)
                        ->orWhere('transaction_type', 'like', "%{$value}%")
                        ->orWhere('balance', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $transactions = new Transaction();
        }


        $transactions = $transactions->where('user_id', $id)->latest()->paginate(Helpers::pagination_limit())->appends($query_param);

        $user = User::find($id);
        return view('admin-views.view.transaction', compact('user', 'transactions', 'search'));
    }

    public function log(Request $request, $id)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $user_logs = $this->user_log_history->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ip_address', 'like', "%{$value}%")
                        ->orWhere('mac_address', 'like', "%{$value}%")
                        ->orWhere('browser', 'like', "%{$value}%")
                        ->orWhere('os', 'like', "%{$value}%")
                        ->orWhere('device_model', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $user_logs = $this->user_log_history;
        }

        $user_logs = $user_logs->with(['user'])->where('user_id', $id)->latest()->paginate(Helpers::pagination_limit())->appends($query_param);

        $user = User::find($id);
        return view('admin-views.view.log', compact('user', 'user_logs', 'search'));
    }

    public function status(Request $request)
    {
        $user = User::find($request->id);
        $user->is_active = !$user->is_active;
        $user->save();
        Toastr::success('Customer status updated!');

        return back();
    }

    public function edit($id)
    {
        $customer = User::find($id);
        return view('admin-views.customer.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'occupation' => 'required',
        ]);

        $customer = User::find($id);

        $customer->f_name = $request->f_name;
        $customer->l_name = $request->l_name;
        $customer->image =  $request->has('image') ? Helpers::update('customer/', $customer->image, 'png', $request->file('image')) : $customer->image;
        $customer->email = $request->has('email') ? $request->email : $customer->email;
        $customer->gender = $request->has('gender') ? $request->gender : $customer->gender;
        $customer->occupation = $request->occupation;
        if ($request->has('password') && strlen($request->password) > 3) {
            $customer->password = bcrypt($request->password);
        }
        $customer->type = CUSTOMER_TYPE;
        $customer->referral_id = $request->referral_id ?? null;
        $customer->save();

        Toastr::success('Customer updated successfully!');
        return redirect(route('admin.customer.list'));
    }

    public function get_kyc_request(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $customers = $this->user->where('is_kyc_verified', '!=', 1)->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
//            return $this->user->where('is_kyc_verified', '!=', 1)->get();
            $customers = $this->user->where('is_kyc_verified', '!=', 1);
        }

        $customers = $customers->orderByDesc('id')->customer()->paginate(Helpers::pagination_limit())->appends($query_param);
        //return gettype($customers[0]->identification_image);
        return view('admin-views.customer.kyc_list', compact('customers', 'search'));
    }

    public function update_kyc_status($id, $status)
    {
        $user = $this->user->find($id);
        if(!isset($user)) {
            Toastr::error(translate('customer not found'));
            return back();
        }
        $user->is_kyc_verified = in_array($status, [1,2]) ? $status : $user->is_kyc_verified;
        $user->save();

        $data = [
            'title' => $status == 1 ? translate('verification_request_is_accepted') : translate('verification_request_is_denied'),
            'description' => '',
            'image' => '',
            'order_id'=>'',
        ];
        send_push_notification_to_device($user->fcm_token, $data);

        Toastr::success(translate('Successfully updated.'));
        return back();
    }

}
