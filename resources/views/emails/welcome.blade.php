<x-mail::message>
# Hello {{ $user->name }},

Welcome to {{ config('app.name') }}!

Your account has been created on our system. You can access the system in one of the following ways:

- Use the default password: `{{ $initialPassword }}` to log in at the link below, then you will be prompted to change it.
- Or, click the button below to set a new password immediately.

<x-mail::button :url="$resetUrl">
Set Your Password
</x-mail::button>

If you prefer to log in first, use this link:
<x-mail::button :url="$loginUrl">
Go to Login
</x-mail::button>

For your security, you may be required to change your password upon first login.

If you did not expect this email, please ignore it.

Regards,
{{ config('app.name') }}
</x-mail::message>
