<script>
if (screen && screen.width > 480) {
  window.smoochScript = $.getScript('https://cdn.smooch.io/smooch.min.js', function(){
    var rzp_email = '';
    var rzp_phone = '';
    if (typeof Smooch === 'undefined') {
      return;
    }

    Smooch
        .init({appToken: '02o6kuyoscqkwiqr3ld3lbehw'})
        .then(function () {
            Smooch._rzpReady = true; // custom prop
        });

    Smooch.on('ready', function(){
      // Show the `email` & `phone` when there are no conversation
      Smooch.getConversation().catch(function(conversation) {
        $('#sk-footer, .app-icon, .app-name, .sk-messages-container').hide();
        var intro_text = 'We are temporarily unavailable on chat, please write to us at <b style="font-weight:bold">support@razorpay.com</b> and we will get back to you asap.';
        if (window.smoochUserLoaded) {
          intro_text += '<br><br>[OR] you can reach us on <br><b style="font-weight: bold">1800-270-0323</b>  for urgent queries.'
        }
        var sk_intro = $('.intro-text').html(intro_text)
          .css({
            fontSize: '14px',
            color: '#444',
            lineHeight: '1.5',
            margin: '16px',
            textAlign: 'justify'
          })
      });
    })
  })
}
</script>
