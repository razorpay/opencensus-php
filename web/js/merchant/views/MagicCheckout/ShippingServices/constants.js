export const RULE_TYPES = {
  FLAT: 'flat',
  SLABS: 'slabs',
  FREE: 'free',
};

export const RULE_TYPES_RADIO_INPUT = [
  {
    value: 'free',
    label: 'Free',
  },
  {
    value: 'flat',
    label: 'Flat Charge',
  },
  {
    value: 'slabs',
    label: 'Slabs',
  },
];

//eslint-disable-next-line
export const EMAIL_REGEX = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

export const STEPS_TEXT = [
  {
    instructions: {
      heading: 'Go to the settings section',
      points: [
        'Go to API Section - on the bottom right (as shown in adjacent pane)',
        { customPointer: true },
      ],
    },
    cta: {
      primary: 'Next',
      secondary: 'Skip Instructions',
      secondaryStep: 2,
    },
  },
  {
    instructions: {
      heading: 'Create an API User',
      subheading: 'In the API section',
      points: [
        'Click on “Create an API User” button ',
        'In the form that opens, enter a new Email ID & choose a password',
        'Click “Generate API credential” button',
      ],
      alternateInstrustion: true,
    },
    cta: {
      secondary: 'Back',
      primary: 'Enter credentials',
      secondaryStep: 0,
    },
  },
];

export const BENEFITS_SHIPROCKET_HIGHLIGHTS = [
  {
    text1: 'To get started with Magic Checkout, you will no longer have to worry about',
    boldText: ' pincode serviceability APIs. ',
    text2: 'Magic checkout will directly fetch serviceability from your Shiprocket account.',
    image: true,
  },
  {
    text1: 'You will be able to configure flat or slab based ',
    boldText: ' Shipping and COD fee ',
    text2: 'from the Magic Checkout dashboard.',
  },
  {
    text1: 'Magic Checkout will fetch ',
    boldText: 'order status updates ',
    text2: 'from your Shiprocket account to provide better RTO protection on your COD orders.',
  },
];
