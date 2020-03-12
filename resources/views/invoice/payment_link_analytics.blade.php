<script>
  // Lamberjack analytics events
  function pushToRzpQ(event, event_options) {
    var eventOptions = event_options || {};
    eventOptions.mode = data.is_test_mode ? 'test' : 'live';

    window.rzpQ.push(
      window.rzpQ
      .now()
      .paymentLink()
      .interaction(event, eventOptions)
    );
  }

  window.addEventListener("load", function() {
    pushToRzpQ('pl.payment.opened');
  });

  function handlePaymentLinkDocURL() {
    window.open('https://www.razorpay.com/payment-links', '_blank');

    pushToRzpQ('pl.payment.redirect');
  }
</script>
