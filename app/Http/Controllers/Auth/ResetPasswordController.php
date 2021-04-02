<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Update the specified Product in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $input = $request->all();
        // dd($input);
        if ($input['password'] == $input['password_confirmation']) {
            $res = DB::table('users')
                ->where('email', $input['email'])
                ->update(['password' => Hash::make($input['password'])]);

                DB::table('password_resets')->where('email', $input['email'])->delete();

            if ($res) {
                return view('auth.login')->withErrors(['(Password Changed Successfully)', 'The Message']);;
            }
            else{ 
                return redirect()->back()->withErrors(['Error! Please Try Again!', 'The Message']);
            }
        }
        else{ 
            return redirect()->back()->withErrors(['Provided passwords do not match! Try Again', 'The Message']);
        }
    }
}
