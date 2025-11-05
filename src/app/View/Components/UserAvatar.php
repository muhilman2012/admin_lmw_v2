<?php

namespace App\View\Components;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Storage;

class UserAvatar extends Component
{
    public User $user;
    public string $size;
    public string $initials;

    public function __construct(User $user, string $size = 'sm')
    {
        $this->user = $user;
        $this->size = $size;
        $this->initials = $this->generateInitials($user->name);
    }
    
    private function generateInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }

    public function hasAvatar(): bool
    {
        return !is_null($this->user->avatar) && trim($this->user->avatar) !== '';
    }

    public function render(): View|Closure|string
    {
        return view('components.user-avatar');
    }
}