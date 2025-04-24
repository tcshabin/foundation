<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Reset Password</title>
</head>
<body>
    <p>Hello,</p>
    <p>Click the link below to reset password:</p>
    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>
    <p>If you did not request this, please ignore the email.</p>
</body>
</html>
