<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Services\BeemSmsService;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

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
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Phone is optional for email registration
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null, // Phone is optional for email registration
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function registered(Request $request, $user)
    {
        $first = trim(explode(' ', $user->name)[0] ?? '');
        $msg = 'Hello ' . ($first ?: 'User') . ' You have successfully registered to Phidtech SMS Login to apply for a sender ID';
        if (!empty($user->phone)) {
            try {
                $sms = new BeemSmsService();
                $sms->sendSms($user->phone, $msg, config('services.beem.default_sender_id'));
            } catch (\Throwable $e) {
            }
        }

        if (!empty($user->email)) {
            try {
                $html = '<h2>Welcome to Phidtech SMS</h2>'
                    . '<p>Hi ' . e($first ?: 'there') . ', your account has been created successfully.</p>'
                    . '<p>Apply for your Sender ID to start sending branded messages:</p>'
                    . '<p><a href="' . route('sender-ids.create') . '" style="display:inline-block;background:#4f46e5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">Apply Sender ID</a></p>'
                    . '<p>Need help? Reply to this email.</p>';
                Mail::html($html, function ($m) use ($user) {
                    $m->to($user->email)->subject('Welcome to Phidtech SMS');
                });
            } catch (\Throwable $e) {
            }
        }
    }
}
