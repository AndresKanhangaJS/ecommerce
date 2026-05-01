<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use  Livewire\Attributes\Title;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

#[Title('Register')]
class RegisterPage extends Component
{
    public $name;
    public $email;
    public $password;

    public function save(){
        $this->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:6|max:255'
        ]);

        //Save to database
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password)
        ]);

        //Login the user
        auth()->login($user);

        //Redirect to home page
        return redirect()->intended();

    }

    public function render()
    {
        return view('livewire.auth.register-page');
    }
}
