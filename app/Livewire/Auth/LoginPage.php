<?php

namespace App\Livewire\Auth;
use  Livewire\Attributes\Title;

use Livewire\Component;

#[Title('Login')]
class LoginPage extends Component
{
    public $email;
    public $password;

    public function login()
    {
        $this->validate([
            'email' => 'required|email|max:255|exists:users,email',
            'password' => 'required|min:6|max:255'
        ]);

        if(!auth()->attempt(['email' => $this->email, 'password' => $this->password])){
            session()->flash('error', 'Invalid Credentials');
        }

        return redirect()->intended();
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
