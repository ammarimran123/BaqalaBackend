<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pre_User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class PreRegisterController extends Controller
{
    //
    // public function __construct()
    // {
    //     $this->middleware('guest')->except('logout');

    // }
    public function register()
    {
        // dd($request);
        // return redirect()->route('pre_registration');
        return view('auth/pre_registration');
    }

    public function save(Request $request)
    {
        $obj = new Pre_User;
        $obj->name = $request->user_name;
        $obj->email = $request->email;
        $obj->phone_no = $request->phone_no;
        $obj->shop_name = $request->shop_name;
        $obj->shop_address = $request->shop_address;
        // Get the value from the form
        $input['email'] = Input::get('email');

        // Must not already exist in the `email` column of `users` table
        $rules = array('email' => 'unique:pre_users,email');

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
        return redirect()->route('pre_registration')->with('failure', 'That email address is already registered.');

            // echo 'That email address is already registered. You sure you don\'t have an account?';
        } else {
            // Register the new user or whatever.
        $obj->save();
        return redirect()->route('pre_registration')->with('status', 'Registration Successful. Our team will contact you soon.');

        }
    }
    
}

