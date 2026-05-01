<?php

namespace App\Livewire\Auth;

use  Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

#[Title('Forgot Password')]
class ForgotPasswordPage extends Component
{
    public $email;

    public function forgotPassword()
    {
        $this->validate([
            'email' => 'required|email|max:255|exists:users,email'
        ]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if($status === Password::RESET_LINK_SENT){
            session()->flash('success', 'Reset password link sent to your email');
            $this->email = '';
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password-page');
    }
}
