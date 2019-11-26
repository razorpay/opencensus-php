// const FRESHCHAT_TOKEN = 'ecf81a9c-2040-43d2-b65f-af058b1508d9'; //test
const FRESHCHAT_TOKEN = '188cc5ce-796c-4918-8029-c2dc1d924274'; //live
const FRESHCHAT_HOST = 'https://wchat.freshchat.com';

const initFreshchat = data => {
  let role = data.userRole;

  if (typeof window.fcWidget !== 'undefined') {
    window.fcWidget.init({
      token: FRESHCHAT_TOKEN,
      host: FRESHCHAT_HOST,
      config: {
        headerProperty: { hideChatButton: true, backgroundColor: '#2E3345' },
      },
    });

    window.fcWidget.setExternalId(data.id);
    window.fcWidget.user.setFirstName(data.name);
    window.fcWidget.user.setEmail(data.email);
    window.fcWidget.user.setProperties({
      activated: data.activated,
      locked: data.locked,
      submitted: data.submitted,
      role,
      userEmail: data.user.email,
      mid: data.id,
      activationStatus: data.activation_status,
      dashboardLink:
        location.origin + '/admin#/app/merchants/' + data.id + '/detail',
    });
  }
};

export default function initChat(data) {
  initFreshchat(data);
}
