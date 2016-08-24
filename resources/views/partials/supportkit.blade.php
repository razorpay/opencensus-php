<script>

window.skFocusListener = function(){
  var props = {};
  var inputs = window.skIntro.find('input');
  var prevent = false;
  inputs.each(function(i, el){
    if(el.validity && !el.validity.valid) {
      el.focus();
      prevent = true;
    }

    if(el.name === 'email' || (el.name === 'phone' && new RegExp(el.getAttribute('pattern')).test(el.value))) {
      props[el.name] = el.value;
    }
  });

  if(prevent){
    return;
  }
  if(!props.email){
    inputs.eq(0).focus();
    return;
  }

  Smooch.updateUser({
    email: props.email,
    properties: props
  });

  window.skIntro.html('');
  $(this).off('focus', window.skFocusListener);
}

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
        if (!window.smoochUserLoaded) {
          window.skIntro = $('.sk-intro').html('Please provide your email or phone number for further communication: <br>\
                <div class="sk-input-wrap"><input value="'+rzp_email+'" name="email" placeholder="Email*" type="email" pattern="^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$"></div>\
                <div class="sk-input-wrap"><input value="'+rzp_phone+'" name="phone" placeholder="10 digit phone number (optional)" type="tel" pattern="[0-9]{10}" maxlength="10"></div>');

          $('#sk-footer input').on('focus', window.skFocusListener);
        }
      });
    })
  })
}
</script>
