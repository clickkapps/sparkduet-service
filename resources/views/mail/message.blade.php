@component('mail::message')

#Dear {{$user->name}}
<br>
<p>{{$message}}</p>
<br>

Thanks,<br>
{{ config('app.name') }}

@endcomponent
