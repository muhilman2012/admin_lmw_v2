<div class="avatar avatar-{{ $size }} rounded-rectangle bg-gray-200" style="position: relative;">
    @if($user->avatar)
        <img src="{{ signMinioUrlSmart('lmw-uploads', $user->avatar, 30) }}" 
             alt="{{ $user->name }}" 
             style="width: 100%; height: 100%; object-fit: cover;">
    @else
        <span>{{ $initials }}</span>
    @endif
</div>