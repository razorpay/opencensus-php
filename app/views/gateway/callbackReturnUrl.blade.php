<!doctype html>
<head>
    <title>Razorpay - Payment in progress</title>
    <style>body{background:#fff;}</style>
</head>

<form action="<?= $url ?>" method="post">
    @foreach ($content as $key => $value)
        <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}" />
    @endforeach
    <input type="submit" />
</form>
</body>
</html>
