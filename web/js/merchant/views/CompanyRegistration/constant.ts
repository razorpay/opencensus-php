export const BANNER_JSON_KEYS = {
  INITIAL: 'initial',
  RESUME: 'resume',
  PROGRESS: 'progress',
  CONGRATULATION: 'congratulation',
};

export const BANNER_DATA = {
  // Screen 1
  [BANNER_JSON_KEYS.INITIAL]: {
    main: {
      firstLine: 'Join 10,000+ businesses who trusted us for',
      secondLine: {
        subText: 'their',
        highlightedText: 'Company Registration',
      },
    },
    midSection: {
      isIconContent: true,
    },
    button: {
      isButtonRequire: true,
      buttonText: 'Register your Business now',
    },
  },
  // Screen 2 - Resume Rize Incorporation  asdasasdas
  [BANNER_JSON_KEYS.RESUME]: {
    main: {
      firstLine: 'Your incorporation Journey has begun',
      secondLine: {
        subText: 'and is in progress',
        highlightedText: '',
      },
    },
    midSection: {
      isIconContent: true,
    },
    button: {
      isButtonRequire: true,
      buttonText: 'Resume Company Registration',
    },
  },
  // Screen 3 - Check Rize Incorporation progress
  [BANNER_JSON_KEYS.PROGRESS]: {
    main: {
      firstLine: 'Your incorporation application is ',
      secondLine: {
        subText: 'under review',
        highlightedText: '',
      },
    },
    midSection: {
      isIconContent: false,
      text: 'Sit back and relax while we take care of the details',
    },
    button: {
      isButtonRequire: false,
    },
  },
  // Screen 4 - Congratulation screen
  [BANNER_JSON_KEYS.CONGRATULATION]: {
    main: {
      firstLine: 'Congratulations! 🎉',
      secondLine: {
        subText: 'Your company is officially a Private Limited Company.',
        // TODO: remove Private Limited Company & update it the actual company type
        highlightedText: '',
      },
    },
    button: {
      isButtonRequire: false, // TODO: hide it till we get download functionality from BE
      buttonText: 'Download Incorporation Documents',
    },
  },
};

export const HEADER_BENEFITS_OFFER = [
  'Affordable Pricing',
  'Instant Resolution',
  'Exclusive Offers on Razorpay Products',
];

export const PACKAGES = [
  'Company Registration',
  'MOA & AOA (If applicable)',
  'LLP Agreement (If applicable)',
  'Company PAN & TAN',
  'Incorporation Certificate',
  'Digital Signature Certificate (DSC)',
  'Company Name Approval',
  'Directors Identification Number (DIN)',
  'DSC Tokens, Support & Shipping',
];

export const OFFERS_DATA = [
  {
    badgeLabel: '3 months free',
    title: 'Business Banking+',
    description: 'Enjoy 3 months free and save 40% for the rest of the year.',
    linkLabel: 'Visit Razorpay X',
    url: 'https://razorpay.com/x/?utm_source=direct&utm_medium=rize_razorpay_dashboard',
  },
  {
    badgeLabel: 'Automated Payroll',
    title: 'Automated Payroll with Razorpay X',
    description:
      'Get 100% off for 1 year for teams with less than 5 employees, or 3 months for larger teams.',
    linkLabel: 'Visit Razorpay Payroll',
    url: 'https://razorpay.com/payroll/?utm_source=direct&utm_medium=rize_razorpay_dashboard',
  },
  {
    badgeLabel: 'Priority support',
    title: 'Seamless payment options',
    description: 'Get priority support and start accepting payments instantly.',
    linkLabel: 'Visit Razorpay Payments',
    url: 'https://razorpay.com/?utm_source=direct&utm_medium=rize_razorpay_dashboard',
  },
];

export const RIZE_INCORPORATION =
  'https://easy.razorpay.com/rize/incorporation/onboarding?utm_source=direct&utm_medium=rize_razorpay_dashboard';

export const EXCLUSIVE_OFFER_HEADER = 'Register to unlock exclusive offers on Razorpay Products';
export const ICORP_PACKAGE_HEADER = 'Your company registration package includes the following';

export const BUSSINESS_TYPE = {
  UNREGISTERED_TYPES: '11',
};
