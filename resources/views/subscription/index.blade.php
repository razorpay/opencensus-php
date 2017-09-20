<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title></title>
  <script>
    <?php if ($_SERVER['HTTP_HOST'] !== "api.razorpay.com"): ?>
    var Razorpay = {
      config: {
        api: '/'
      }
    };
    <?php endif; ?>
  </script>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
<script src='https://cdn.razorpay.com/static/hosted/subscription.js'></script>
<script>
var $ = document.querySelector.bind(document);

var options = {
    "key": {!! json_encode($data['merchant']['key']) !!},
    "amount": window.o.total,
    "image": {!! json_encode($data['merchant']['image']) !!},
    "subscription_id": {!! json_encode($data['subscription']['id']) !!},
    "handler": function (response) {
      // success
      if (typeof response.error_code === 'undefined') {
        $('body').className = 'show-modal';

        $('.full-overlay').style.display = 'block';
        $('.modal').style.display = 'block';

        $('.modal').innerHTML = "Payment Successful";
      }
    },
    "prefill": {
        "name": {!! json_encode($data['customer']['name']) !!},
        "email": {!! json_encode($data['customer']['email']) !!}
    },
    "notes": {
        "address": "Hello World"
    },
    "theme": {
        "color": "#F37254"
    }
};

var rzp1 = new Razorpay(options);

var checkoutHandler = function(e) {
    rzp1.open();
    e.preventDefault();
};

$('.pay-btn1').onclick = checkoutHandler;

$('.pay-btn1-mobile').onclick = checkoutHandler;

$('.full-overlay').addEventListener('click', function (e) {

  $('body').className = '';

  setTimeout(function () {
     $('.full-overlay').style.display = 'none';
     $('.modal').style.display = 'none';

     location.reload();
  }, 300);

}, false);

</script>