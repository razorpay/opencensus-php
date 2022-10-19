import { getDeviceSource } from 'merchant/components/Support/getCommonSupportProperties';

// const FRESHCHAT_TOKEN = 'ecf81a9c-2040-43d2-b65f-af058b1508d9'; //test
const FRESHCHAT_TOKEN = '5f1b4ead-651e-472b-afa8-a94d7fa3873f'; //live
const FRESHCHAT_HOST = 'https://wchat.in.freshchat.com';

let isScriptLoaded = false;

const initFreshchat = (data) => {
  const role = data.userRole;
  const { isFreshChatbotLive } = data;

  if (typeof window.fcWidget !== 'undefined') {
    window.fcWidget.init({
      token: FRESHCHAT_TOKEN,
      host: FRESHCHAT_HOST,
      config: {
        headerProperty: { hideChatButton: true, backgroundColor: '#2E3345' },
      },
      ...(isFreshChatbotLive ? { tags: ['enablefreshchatBot'] } : {}),
    });

    window.fcWidget.setExternalId(data.id);
    window.fcWidget.user.setFirstName(data.name);
    window.fcWidget.user.setEmail(data.email);
    window.fcWidget.user.setProperties({
      activated: data.activated,
      locked: data.locked,
      submitted: data.submitted,
      role,
      MerchantID: data?.id,
      userEmail: data.user.email,
      mid: data.id,
      activationStatus: data.activation_status,
      dashboardLink: `${location.origin}/admin#/app/merchants/${data.id}/detail`,
      name: data.user.name,
      contactNo: data.user.contact_mobile,
      source: getDeviceSource(),
      isContextual: isFreshChatbotLive,
    });
  }
};

function initScript(data, onLoad) {
  const tag = document.createElement('script');
  tag.type = 'text/javascript';
  tag.src = 'https://wchat.freshchat.com/js/widget.js';
  tag.onload = () => {
    isScriptLoaded = true;
    initFreshchat(data);
    onLoad();
  };
  document.body.appendChild(tag);
}

export default function initChat(data, onLoad) {
  if (isScriptLoaded) {
    initFreshchat(data);
    onLoad();
  } else {
    initScript(data, onLoad);
  }
}
