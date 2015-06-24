<!doctype html>
<head>
    <title>Razorpay - Payment in progress</title>
    <style>body{background:#fff;}</style>
</head>

<form action="<?= $data['request']['url'] ?>" method="post">
    @foreach ($data['request']['content'] as $key => $value)
        <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}" />
    @endforeach
    <input type="submit" />
</form>
</body>
</html>
