@component('mail::message')

{{$user->name ? 'Dear ' . $user->name : 'Hello,'}}
<br>
<p>{{$message}}</p>
<br>

Thanks,<br>
{{ config('app.name') . ' Team '}}

@endcomponent
