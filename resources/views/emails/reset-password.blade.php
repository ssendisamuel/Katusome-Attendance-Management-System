<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Reset Your Password</title>
  </head>
  <body>
    <p>Hello {{ $user->name ?? 'User' }},</p>
    <p>We received a request to reset your password. Click the link below to proceed:</p>
    <p><a href="{{ $url }}">Reset Password</a></p>
    <p>If you did not request a password reset, you can safely ignore this email.</p>
    <p>Regards,<br/>Support Team</p>
  </body>
</html>