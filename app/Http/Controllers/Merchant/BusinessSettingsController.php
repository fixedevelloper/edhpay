<?php

namespace App\Http\Controllers\Merchant;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class BusinessSettingsController extends Controller
{
    public function shop_index()
    {
        $merchant = Merchant::where(['user_id' => auth()->user()->id])->first();
        //return $merchant;
        return view('merchant-views.business-settings.shop-index', compact('merchant'));
    }

    public function shop_update(Request $request)
    {
        $merchant = Merchant::where(['user_id' => auth()->user()->id])->first();

        if ($request->has('logo')) {
            $logo = Helpers::update('merchant/', $merchant->logo, 'png', $request->file('logo'));
        } else {
            $logo = $merchant['logo'];
        }

        $merchant->store_name = $request->store_name;
        $merchant->callback = $request->callback;
        $merchant->address = $request->address;
        $merchant->bin = $request->bin;
        $merchant->logo = $logo;
        $merchant->update();

        Toastr::success(translate('Successfully updated.'));
        return back();
    }

    public function integration_index()
    {
        $merchant = Merchant::where(['user_id' => auth()->user()->id])->first();
        return view('merchant-views.business-settings.integration-index', compact('merchant'));
    }

    public function integration_update(Request $request)
    {
        $merchant = Merchant::where(['user_id' => auth()->user()->id])->first();
        $merchant->public_key = Str::random(50);
        $merchant->secret_key = Str::random(50);
        $merchant->update();

        return response()->json([
            'merchant' => $merchant,
            'success' => 'Successfully updated.'
        ]);
    }



}
