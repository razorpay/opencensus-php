<script>
  // Lamberjack analytics events
  function pushToRzpQ(event, event_options) {
    window.rzpQ.push(
      window.rzpQ
      .now()
      .paymentLink()
      .success(event, event_options)
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