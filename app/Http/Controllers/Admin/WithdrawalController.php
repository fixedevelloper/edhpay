<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\helpers;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    private $withdrawalMethod;

    public function __construct(WithdrawalMethod $withdrawal_method)
    {
        $this->withdrawal_method = $withdrawal_method;
    }

    public function add_method(Request $request)
    {
        $withdrawal_methods = $this->withdrawal_method->latest()->paginate(Helpers::pagination_limit());
        return view('admin-views.withdrawal.index', compact('withdrawal_methods'));
    }

    public function store_method(Request $request)
    {
        $request->validate([
            'method_name' => 'required',
            'field_type' => 'required|array',
            'field_name' => 'required|array',
            'placeholder' => 'required|array',
        ]);

        $method_fields = [];
        foreach ($request->field_name as $key=>$field_name) {
            $method_fields[] = [
                'input_type' => $request->field_type[$key],
                'input_name' => strtolower(str_replace(' ', "_", $request->field_name[$key])),
                'placeholder' => $request->placeholder[$key],
            ];
        }

        $this->withdrawal_method->updateOrCreate(
            ['method_name' => $request->method_name],
            ['method_fields' => $method_fields]
        );

        Toastr::success('successfully added');
        return back();
    }

    public function delete_method(Request $request)
    {
        $withdrawal_methods = $this->withdrawal_method->find($request->id);
        $withdrawal_methods->delete();

        Toastr::success(translate('successfully removed'));
        return back();
    }
}
