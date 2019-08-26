<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta charset='utf-8'>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="robots" content="noindex">
  <title></title>
  <script>
    @if (isset($_SERVER['HTTP_HOST']))
    <?php if ($_SERVER['HTTP_HOST'] !== "api.razorpay.com"): ?>
    var Razorpay = {
      config: {
        api: '/'
      }
    };
    <?php endif; ?>
    @endif
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
        window.o.upfrontAmount = function(addons) {
          return window.o.amount(addons.reduce(function(subTotal, addon) {
              return subTotal + (addon.item.amount * addon.quantity);
          },0));
        };
        window.o.due_on = o.subscription.charge_at;
        window.o.addons = o.subscription.addons;
    </script>
</body>
</html>
<script src='https://cdn.razorpay.com/static/hosted/subscription.js'></script>
<script>
var $ = document.querySelector.bind(document);
ansh = {!! json_encode($data) !!};
var options = {
    "key": {!! json_encode($data['key_id']) !!},
    "image": {!! json_encode($data['merchant']['image']) !!},
    "subscription_id": {!! json_encode($data['subscription']['id']) !!},
    "subscription_card_change": {!! json_encode((int)$data['subscription']['card_change_status']) !!},
    "handler": function (response) {
      // success
      if (typeof response.error_code === 'undefined') {
        $('body').className = 'show-modal';

        $('.full-overlay').style.display = 'block';
        $('.modal').style.display = 'block';

        $('.modal').innerHTML = "Payment Successful";
        setTimeout(function() {
          location.reload();
        }, 1000)
      }
    },
    callback_url: location.href,
    "prefill": {
        <?php if (empty($data['customer']) === false): ?>
        "name": {!! json_encode($data['customer']['name']) !!},
        "email": {!! json_encode($data['customer']['email']) !!}
        <?php endif; ?>
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
