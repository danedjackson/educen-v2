<x-mail::message>
# Teacher Registration

You have been added as a teacher in the Teacher Portal.<br><br>

You will be prompted to enter a password to access the web application. <br><br>

Please click the button below and enter your desired password to complete your registration and access the Teacher Portal.

<x-mail::button :url="$link">
Login to Teacher Portal
</x-mail::button>

Alternatively, you can use the following temporary password to log in and set your own password later:<br>
{{ $password }}<br><br>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
