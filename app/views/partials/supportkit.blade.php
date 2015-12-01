<script>
var intro_text = '';
var sk_ready = false;

if (screen && screen.width > 480) {
  $($.getScript('https://cdn.smooch.io/smooch.min.js', function(){
    var rzp_email = '';
    var rzp_phone = '';
    if (typeof Smooch === 'undefined') {
      return;
    }

    Smooch.init({appToken: '02o6kuyoscqkwiqr3ld3lbehw'});

    Smooch.on('ready', function(){
      sk_ready = true;
      if(Smooch.user.get('email')){
        return;
      }
      intro_text = $('.sk-intro').html();
      sk_intro = $('.sk-intro').html('Please provide your email or phone number for further communication: <br>\
                          <div class="sk-input-wrap"><input value="'+rzp_email+'" name="email" placeholder="Email*" type="email" pattern="^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$"></div>\
                          <div class="sk-input-wrap"><input value="'+rzp_phone+'" name="phone" placeholder="10 digit phone number (optional)" type="tel" pattern="[0-9]{10}" maxlength="10"></div>');

      $('#sk-header').click(function(){
        setTimeout(function(){
          if($('#sk-container').hasClass('sk-appear')){
            setTimeout(function(){
              sk_intro.find('input:eq(0)').focus();
              if(!window.sklistener){
                window.sklistener = $('#sk-footer input').on('focus', chat_attempt);
              }
            }, 420)
          }
        }, 0)
      })

      function chat_attempt(){
        var props = {};
        var inputs = sk_intro.find('input');
        var prevent = false;
        inputs.each(function(i, el){
          if(el.validity && !el.validity.valid){
            el.focus();
            prevent = true;
          }
          if(el.name === 'phone' && new RegExp(el.getAttribute('pattern')).test(el.value)){
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

        sk_intro.html(intro_text);
        $(this).off('focus', chat_attempt);

      }
    })
  }))
}
</script>