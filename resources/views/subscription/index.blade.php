<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <title></title>
</head>
<body>
    <script>
        window.o = {!! json_encode($data) !!};

        window.o.total = o.subscription.quantity * o.plan.item.amount;
        window.o.amount = function(amount) {
            return (
              '₹' +
              (amount / 100)
                .toFixed(2)
                .replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,')
                .replace('.00', '')
            );
        };
        window.o.due_on = '18 October 2017';
        window.o.addons = [];
    </script>
</body>
</html>
<script src='http://localhost:8080/subscription.js'></script>
<script src="http://localhost:35729/livereload.js?snipver=1"></script>