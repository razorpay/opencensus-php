import InfoPoints from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/components/InfoPoints';
import IThinkForm from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/components/IThinkForm';

export const STEP_TEXTS = [
  {
    header: 'Link iThink Logistics account',
    desc: 'Magic Checkout will able to receive realtime delivery statuses for better RTO protection',
    Component: InfoPoints,
  },
  {
    header: 'Enter your iThink Logistics API credentials',
    desc: 'Please enter your iThink Logistics API credentials:',
    Component: IThinkForm,
  },
];

export const DEMO_INFO_TEXT = 'Follow the steps to link iThink Logistics account';
export const DEMO_VIDEO_LINK =
  'https://cdn.razorpay.com/static/assets/magic-checkout/ithink-logistics-integration-video.mp4';
