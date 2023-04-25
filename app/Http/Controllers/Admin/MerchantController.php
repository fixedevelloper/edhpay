<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\EMoney;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogHistory;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;

class MerchantController extends Controller
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
        return view('admin-views.merchant.index', compact('current_user_info'));
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $merchants = User::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $merchants = new User();
        }

        $merchants = $merchants->with('merchant')->merchantUser()->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        //return($merchants) ;
        return view('admin-views.merchant.list', compact('merchants', 'search'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'image' => 'required',
            'country_code' => 'required',
            'phone' => 'required|unique:users|min:8|max:20',
            'password' => 'required|min:8',
            'identification_type' => 'required',
            'identification_number' => 'required',
            'store_name' => 'required',
            'callback' => 'required',
            'address' => 'required',
            'bin' => 'required',
            'logo' => 'required',
            'identification_image' => 'required',
        ],[
            'password.min' => translate('PIN must contain 8 characters'),
            'country_code.required' => translate('Country code select is required')
        ]);

        $phone = $request->country_code . $request->phone;
        $merchant = User::where(['phone' => $phone])->first();
        if (isset($merchant)){
            Toastr::warning(translate('This phone number is already taken'));
            return back();
        }

        try {
            DB::beginTransaction();

            if ($request->has('image')) {
                $image_name = Helpers::upload('merchant/', 'png', $request->file('image'));
            } else {
                $image_name = 'def.png';
            }

            if ($request->has('logo')) {
                $logo = Helpers::upload('merchant/', 'png', $request->file('logo'));
            } else {
                $logo = 'def.png';
            }

            $id_img_names = [];
            if (!empty($request->file('identification_image'))) {
                foreach ($request->identification_image as $img) {
                    $identity_image = Helpers::upload('merchant/', 'png', $img);
                    $id_img_names[] = $identity_image;
                }
                $identity_image = json_encode($id_img_names);
            } else {
                $identity_image = json_encode([]);
            }

            $user = new User();
            $user->f_name = $request->f_name;
            $user->l_name = $request->l_name;
            $user->email = $request->email;
            $user->dial_country_code = $request->country_code;
            $user->phone = $phone;
            $user->password = bcrypt($request->password);
            $user->type = MERCHANT_TYPE;    //['Admin'=>0, 'Agent'=>1, 'Customer'=>2, 'Merchant'=>3]
            $user->image = $image_name;
            $user->identification_type = $request->identification_type;
            $user->identification_number = $request->identification_number;
            $user->identification_image = $identity_image;
            $user->is_kyc_verified = 1;
            $user->save();

            $user->find($user->id);
            $user->unique_id = $user->id . mt_rand(1111, 99999);
            $user->save();

            $merchant = new Merchant();
            $merchant->user_id = $user->id;
            $merchant->store_name = $request->store_name;
            $merchant->callback = $request->callback;
            $merchant->address = $request->address;
            $merchant->bin = $request->bin;
            $merchant->logo = $logo;
            $merchant->public_key = Str::random(50);
            $merchant->secret_key = Str::random(50);
            $merchant->merchant_number = $request->phone;
            $merchant->save();

            $emoney = new EMoney();
            $emoney->user_id = $user->id;
            $emoney->save();

            DB::commit();

            Toastr::success(translate('Merchant Added Successfully!'));
            return redirect()->route('admin.merchant.list');
        }catch (\Exception $exception){
            //return $exception->getMessage();
            DB::rollBack();
            Toastr::warning(translate('Merchant Added Failed!'));
            return back();
        }
    }

    public function status(Request $request)
    {
        $user = User::find($request->id);
        $user->is_active = !$user->is_active;
        $user->save();
        Toastr::success('Merchant status updated!');
        return back();
    }

    public function edit($id)
    {
        $user = User::find($id);
        $merchant = Merchant::where(['user_id' => $user->id])->first();
        //return $merchant;
        return view('admin-views.merchant.edit', compact('user','merchant' ));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'identification_type' => 'required',
            'identification_number' => 'required',
            'store_name' => 'required',
            'callback' => 'required',
            'address' => 'required',
            'bin' => 'required',
        ],[

        ]);

        try {
            DB::beginTransaction();

            $user = User::find($id);
            $merchant = Merchant::where(['user_id' => $user->id])->first();

            if ($request->has('image')) {
                $image_name = Helpers::update('merchant/', $user->image, 'png', $request->file('image'));
            } else {
                $image_name = $user['image'];
            }

            if ($request->has('logo')) {
                $logo = Helpers::update('merchant/', $merchant->logo, 'png', $request->file('logo'));
            } else {
                $logo = $merchant['logo'];
            }

            if ($request->has('identification_image')){
                foreach (json_decode($user['identification_image'], true) as $img) {
                    if (Storage::disk('public')->exists('merchant/' . $img)) {
                        Storage::disk('public')->delete('merchant/' . $img);
                    }
                }
                $img_keeper = [];
                foreach ($request->identification_image as $img) {
                    $identity_image = Helpers::upload('merchant/', 'png', $img);
                    $img_keeper[] = $identity_image;
                }
                $identity_image = json_encode($img_keeper);
            } else {
                $identity_image = $user['identification_image'];
            }

            $user->f_name = $request->f_name;
            $user->l_name = $request->l_name;
            $user->email = $request->has('email') ? $request->email : $user->email;

            if ($request->has('password') && strlen($request->password) > 7) {
                $user->password = bcrypt($request->password);
            }

            $user->image = $image_name;
            $user->identification_type = $request->identification_type;
            $user->identification_number = $request->identification_number;
            $user->identification_image = $identity_image;
            $user->update();

            $merchant->user_id = $user->id;
            $merchant->store_name = $request->store_name;
            $merchant->callback = $request->callback;
            $merchant->address = $request->address;
            $merchant->bin = $request->bin;
            $merchant->logo = $logo;
            $merchant->update();

            DB::commit();

            Toastr::success(translate('Merchant Updated Successfully!'));
            return redirect()->route('admin.merchant.list');
        }catch (\Exception $exception){
            //dd($exception->getMessage());
            DB::rollBack();
            Toastr::warning(translate('Merchant Updated Failed!'));
            return back();
        }
    }

    public function view($id)
    {
        $user = User::with('emoney', 'merchant')->find($id);
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


}
