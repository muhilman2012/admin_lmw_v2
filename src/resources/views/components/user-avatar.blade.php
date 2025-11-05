<div class="avatar avatar-{{ $size }} rounded-rectangle bg-gray-200">
    @if($user->avatar)
        <img src="{{ Storage::disk('uploads')->url($user->avatar) }}" alt="{{ $user->name }}" class="avatar avatar-{{ $size }} rounded-rectangle">
    @else
        <span>{{ $initials }}</span>
    @endif
</div>