import { getDeviceSource } from 'merchant/components/Support/getCommonSupportProperties';

// const FRESHCHAT_TOKEN = 'ecf81a9c-2040-43d2-b65f-af058b1508d9'; //test
const FRESHCHAT_TOKEN = '5f1b4ead-651e-472b-afa8-a94d7fa3873f'; //live
const FRESHCHAT_HOST = 'https://wchat.in.freshchat.com';

const CLOSING_TEXTS = [
  'Thank you for sharing your experience.',
  'We will be closing the conversation as you seem to be away. Please feel free to connect back in case you need any further assistance. We will be happy to help! Thank you for chatting with Razorpay.',
  'We are closing the conversation as you have been inactive for more than 5 minutes.',
  'Thank you so much for using “Razorpay” chat service. We hope we will hear from you soon! Have a good day.',
  'Sure! We are restarting the conversation',
];

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
    if (isFreshChatbotLive) {
      window.fcWidget.on('message:received', (payload) => {
        if (CLOSING_TEXTS.includes(payload?.message?.messageFragments?.[0]?.content)) {
          window.fcWidget.destroy();
          window.fcWidget.on('widget:destroyed', () => {
            initFreshchat(data);
          });
        }
      });
    }
  }
};

export default function initChat(data) {
  initFreshchat(data);
}
