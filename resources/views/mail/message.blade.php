@component('mail::message')

{{$name ? 'Dear ' . $name : 'Hello,'}}
<br>
<p>{{$message}}</p>
<br>

Thanks,<br>
{{ config('app.name') . ' Team '}}

@endcomponent
