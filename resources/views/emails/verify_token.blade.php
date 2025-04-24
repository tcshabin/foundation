<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Complete Registration</title>
</head>
<body>
    <p>Hello,</p>
    <p>Click the link below to complete your registration:</p>
    <p><a href="{{ $registerUrl }}">{{ $registerUrl }}</a></p>
    <p>If you did not request this, please ignore the email.</p>
</body>
</html>
