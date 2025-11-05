<div class="avatar avatar-{{ $size }} rounded-rectangle bg-gray-200">
    @if($user->avatar)
        <img src="{{ signMinioUrlSmart('lmw-uploads', $user->avatar, 30) }}" 
             alt="{{ $user->name }}" 
             class="avatar avatar-{{ $size }} rounded-rectangle">
    @else
        <span>{{ $initials }}</span>
    @endif
</div>