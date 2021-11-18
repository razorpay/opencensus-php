export const initChatbot = ({ user, merchant }) => {
  const body = {
    email: user.email,
    name: user.name,
    contactNo: user.contact_mobile,
    merchantId: merchant.id,
  };
  window.ymConfig = {
    bot: 'x1626377294915',
    host: 'https://cloud.yellow.ai',
    partiallyOpen: false,
    alignLeft: 'right',
    payload: body,
  };


  (function () {
    var w = window,
      ic = w.YellowMessenger;
    if ('function' === typeof ic)
      ic('reattach_activator'), ic('update', ymConfig)
    else {
      var d = document,
        i = function () {
          i.c(arguments)
        }

      function l() {
        var e = d.createElement('script')
          ; (e.type = 'text/javascript'),
            (e.async = !0),
            (e.src =
              'https://cdn.yellowmessenger.com/plugin/widget-v2/latest/dist/main.min.js')
        var t = d.getElementsByTagName('script')[0];
        t.parentNode.insertBefore(e, t);
        addCloseBtn();
      }
      (i.q = []),
        (i.c = function (e) {
          i.q.push(e);
        }),
        (w.YellowMessenger = i),
        w.attachEvent ? w.attachEvent('onload', l) : w.addEventListener('load', l, !1);
    }
  })();

  const addCloseBtn = () => {
    const checkForClose = setInterval(() => {
      const botIcon = document.querySelector('#ymDivBar')
      if (botIcon) {
        window.chatbotToggle = () => botIcon.click()
        const closeBtn = document.createElement('div')
        closeBtn.className = 'minimize-bot'
        closeBtn.innerHTML = `<i class="icon icon-ic_close bot-close-icon"></i>`
        const container = document.querySelector('.ym-loading-spinner')
        container.parentNode.insertBefore(closeBtn, container)
        window.chatBotCloseIcon = closeBtn;
        clearInterval(checkForClose);
      }
    }, 300)
  }
}
