import Smooch from 'smooch';

// TODO: use prod credentials for freshchat
const SMOOCH_APP_ID = '54d849a9c99af8250046dbf8';
const FRESHCHAT_TOKEN = 'ecf81a9c-2040-43d2-b65f-af058b1508d9';
const FRESHCHAT_HOST = 'https://wchat.freshchat.com';

const initSmooch = data => {
  let role = data.userRole;
  Smooch.init({
    appId: SMOOCH_APP_ID,
    displayStyle: 'button',
    buttonHeight: '0',
    buttonWidth: '0',
  }).then(function() {
    Smooch.updateUser({
      givenName: data.name,
      email: data.email,
      properties: {
        id: data.id,
        activated: data.activated,
        locked: data.locked,
        submitted: data.submitted,
        role: role,
        userEmail: data.user.email,
        dashboardLink:
          location.origin + '/admin#/app/merchants/' + data.id + '/detail',
      },
    });
  });

  // export smooch instance to window object
  window.Smooch = Smooch;
};

const initFreshchat = data => {
  let role = data.userRole;
  fcWidget.init({
    token: FRESHCHAT_TOKEN,
    host: FRESHCHAT_HOST,
    config: {
      headerProperty: { hideChatButton: true },
    },
  });
  fcWidget.setExternalId(data.id);
  fcWidget.user.setFirstName(data.name);
  fcWidget.user.setEmail(data.email);
  fcWidget.user.setProperties({
    activated: data.activated,
    locked: data.locked,
    submitted: data.submitted,
    role,
    userEmail: data.user.email,
    dashboardLink:
      location.origin + '/admin#/app/merchants/' + data.id + '/detail',
  });
};

export default function initChat(data) {
  const chatExp = window.rzp_user.experiments.chat_new || {};

  if (location.hostname === 'dashboard.razorpay.com') {
    if (chatExp.result === 'on') {
      initFreshchat(data);
    } else {
      initSmooch(data);
    }
  }
}
