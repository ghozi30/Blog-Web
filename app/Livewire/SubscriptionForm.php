<?php

namespace App\Livewire;

use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SubscriptionForm extends Component
{
    #[Validate('required|email|unique:subscribers,email', message: [
        'email.required' => 'Please enter your email address.',
        'email.email' => 'Please enter a valid email address.',
        'email.unique' => 'This email is already subscribed!',
    ])]
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate();

        Subscriber::create([
            'email' => $this->email,
            'token' => Str::random(32),
            'is_verified' => false,
            'verified_at' => null,
        ]);

        $this->subscribed = true;
        $this->reset('email');
    }

    public function render(): View
    {
        return view('livewire.subscription-form');
    }
}
