export const pricing_bundle = {
  featureIdOrder: ['f1', 'f2', 'f3', 'f4', 'f5', 'f6', 'f7', 'f8', 'f9'],
  featureIdToFeatureCopyMap: {
    f1: 'Receive Payments for FREE per month upto',
    f2: 'Get guaranteed benefits and savings',
    f3: 'Settlement Period',
    f4: 'Customer Support',
    f5: 'Account Management',
    f6: 'Proprietary Reports',
    f7: 'Exclusive Masterclasses and Consultant Access from Industry Experts',
    f8: 'Transaction Fee post free limit:',
    f9: 'Exclusive offers and Benefits at ₹0 additional cost',
  },
  header: {
    icon: {
      alt: 'Fire GIF',
      src: 'https://betacdn.np.razorpay.in/static/assets/growth-assets/pricing-bundle/fire.svg',
    },
    pillText: ':fire: 16% OFF',
    title: 'Pay for just 10 Months, use for a Year by switching to Annual Plan!',
  },
  heroImage: {
    alt: 'Rocket',
    src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/heroRocket.svg',
  },
  id: 'Pricing_bundle_FEB0323_JSON_SCHEMA_LAM8AdqMpYUSkY',
  pricingPlans: [
    {
      annualPrice: 999,
      button: {
        label: 'Choose this plan',
        variant: 'secondary',
      },
      description: 'Are you looking for an all-in-one Digital Payments Engine?',
      f1: '₹ 10,000',
      f2: 'worth ₹4200',
      f3: 'STANDARD | T+2',
      f4: 'STANDARD 72 hour Resolution',
      f5: '',
      f6: 'No Industry Reports',
      f7: 'Extra Charges',
      f8: '2%',
      f9: 'Shiprocket 100% cash-back up to Rs. 1000',
      icon: {
        alt: 'Shield',
        src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/shield.svg',
      },
      id: 'LD7yJOWwZ8Kcvv',
      isRecommended: false,
      monthlyPrice: 99,
      notIncludedFeatureOfferings: ['f6', 'f7'],
      title: 'Get Onboard Package',
    },
    {
      annualPrice: 5499,
      button: {
        label: 'Choose this plan',
        variant: 'secondary',
      },
      description: 'Are you a small businesses who has started accepting payments at scale?',
      f1: '₹ 30,000',
      f2: 'worth ₹73,600',
      f3: 'STANDARD | T+2',
      f4: 'PREMIUM 48 Hour Resolution',
      f5: 'Dedicated Account Manager',
      f6: 'No Industry Reports',
      f7: 'Extra Charges',
      f8: '1.9% (5% Off)',
      f9: 'Shiprocket 100% cash-back up to Rs. 1000',
      icon: {
        alt: 'Sparkle',
        src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/star.svg',
      },
      id: 'LD7x4J2q8g4ung',
      monthlyPrice: 549,
      notIncludedFeatureOfferings: ['f7'],
      title: 'Take Off Package',
    },
    {
      annualPrice: 11999,
      button: {
        label: 'Choose this plan',
        variant: 'secondary',
      },
      description: 'Are you a small to medium businesses collecting upto Rs 50,000?',
      f1: '₹ 50,000',
      f2: 'worth ₹2,08,000',
      f3: 'EARLY | T+1',
      f4: 'PREMIUM 48 Hour Resolution',
      f5: 'Dedicated Account Manager',
      f6: '1. Industry trends 2. Peer product adoption',
      f7: 'Free worth Rs 20,000',
      f8: '1.9% (5% Off)',
      f9: '10% off on Google Workspace on recurring billing 2. Shiprocket 100% cash-back up to Rs. 1000',
      icon: {
        alt: 'Trend Up',
        src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/growth.svg',
      },
      id: 'LD7tbBUZbSMZVp',
      monthlyPrice: 1199,
      title: 'Growth Package',
    },
    {
      annualPrice: 12999,
      button: {
        label: 'Get Started Now!',
        variant: 'primary',
      },
      description: 'Are you a growing business who requires faster settlement?',
      f1: '₹ 75,000',
      f2: 'worth ₹2,61,000',
      f3: 'EARLY | T+1',
      f4: 'ELITE On Call Resolution',
      f5: 'Dedicated Account Manager',
      f6: '1. Industry trends 2. Peer product adoption 3. Success Rate Report',
      f7: 'Free worth Rs 20,000',
      f8: '1.85% (7.5% Off)',
      f9: '10% off on Google Workspace on recurring billing 2. Shiprocket 100% cash-back up to Rs. 1000',
      icon: {
        alt: 'Jet',
        src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/rocket.svg',
      },
      id: 'LD7w1SXw34MOVO',
      isRecommended: true,
      monthlyPrice: 1299,
      title: 'Jumbo Jet Package',
    },
    {
      annualPrice: 19999,
      button: {
        label: 'Choose this plan',
        variant: 'primary',
      },
      description: "Are you a large business, who's looking for faster resolutions?",
      f1: '₹ 1,50,000',
      f2: 'worth ₹3,63,000',
      f3: 'INSTANT | Same Day',
      f4: 'ELITE On Call Resolution',
      f5: 'Dedicated Account Manager',
      f6: '1. Industry trends 2. Peer product adoption 3. Success Rate Report 4. 1:1 monthly session for Settlement report debrief',
      f7: 'Free worth Rs 20,000',
      f8: '1.75% (12.5% Off)',
      f9: '10% off on Google Workspace on recurring billing 2. $1,000 in credits for Notion Team Plan 3. Shiprocket 100% cash-back up to Rs. 1000',
      icon: {
        alt: 'Spaceship',
        src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/spaceship.svg',
      },
      id: 'LD7zJb2z2KL3Rq',
      monthlyPrice: 1999,
      title: 'Spaceship Package',
    },
  ],
  theme: 'purple',
  tracking_data: {},
};
export const reduxState = {
  user: {
    isBundlePricingEnabled: true,
  },
  location: window.location,
  mode: 'live',
  pricing_bundles_obj: { ...pricing_bundle },
};

export const pricingPlans = [
  {
    annualPrice: 999,
    button: {
      label: 'Buy this plan',
      variant: 'secondary',
    },
    description: 'Are you looking for an all-in-one Digital Payments Engine?',
    f1: {
      annual: '\u20b9 10,000',
      monthly: '\u20b9 10,000',
    },
    f2: {
      annual: 'worth \u20b95000',
      monthly: 'worth \u20b94200',
    },
    f3: {
      annual: 'STANDARD | T+2',
      monthly: 'STANDARD | T+2',
    },
    f4: {
      annual: 'STANDARD 72 hour Resolution',
      monthly: 'STANDARD 72 hour Resolution',
    },
    f5: {
      annual: '',
      monthly: '',
    },
    f6: {
      annual: 'No Industry Reports',
      monthly: 'No Industry Reports',
    },
    f7: {
      annual: 'Extra Charges',
      monthly: 'Extra Charges',
    },
    f8: {
      annual: '2%',
      monthly: '2%',
    },
    f9: {
      annual: '\u2022Shiprocket 100% cash-back up to Rs. 1000',
      monthly: '\u2022Shiprocket 100% cash-back up to Rs. 1000',
    },
    icon: {
      alt: 'Shield',
      src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/shield.svg',
    },
    id: 'LD7yJOWwZ8Kcvv',
    isRecommended: false,
    monthlyPrice: 99,
    notIncludedFeatureOfferings: ['f6', 'f7'],
    title: 'Get Onboard',
  },
  {
    annualPrice: 5499,
    button: {
      label: 'Buy this plan',
      variant: 'secondary',
    },
    description: 'Are you a small businesses who has started accepting payments at scale?',
    f1: {
      annual: '\u20b9 30,000',
      monthly: '\u20b9 30,000',
    },
    f2: {
      annual: 'worth \u20b980,600',
      monthly: 'worth \u20b973,600',
    },
    f3: {
      annual: 'STANDARD | T+2',
      monthly: 'STANDARD | T+2',
    },
    f4: {
      annual: 'PREMIUM 48 Hour Resolution',
      monthly: 'PREMIUM 48 Hour Resolution',
    },
    f5: {
      annual: 'Dedicated Account Manager',
      monthly: 'Dedicated Account Manager',
    },
    f6: {
      annual: 'No Industry Reports',
      monthly: 'No Industry Reports',
    },
    f7: {
      annual: 'Extra Charges',
      monthly: 'Extra Charges',
    },
    f8: {
      annual: '1.9% (5% Off)',
      monthly: '1.9% (5% Off)',
    },
    f9: {
      annual:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
      monthly: '\u2022 Shiprocket 100% cash-back up to Rs. 1000',
    },
    icon: {
      alt: 'Sparkle',
      src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/star.svg',
    },
    id: 'LD7x4J2q8g4ung',
    monthlyPrice: 549,
    notIncludedFeatureOfferings: ['f7'],
    title: 'Take Off',
  },
  {
    annualPrice: 11999,
    button: {
      label: 'Buy this plan',
      variant: 'secondary',
    },
    description: 'Are you a small to medium businesses collecting upto Rs 50,000?',
    f1: {
      annual: '\u20b9 50,000',
      monthly: '\u20b9 50,000',
    },
    f2: {
      annual: 'worth \u20b92,50,000',
      monthly: 'worth \u20b92,08,000',
    },
    f3: {
      annual: 'EARLY | T+1',
      monthly: 'EARLY | T+1',
    },
    f4: {
      annual: 'PREMIUM 48 Hour Resolution',
      monthly: 'PREMIUM 48 Hour Resolution',
    },
    f5: {
      annual: 'Dedicated Account Manager',
      monthly: 'Dedicated Account Manager',
    },
    f6: {
      annual: '1. Industry trends 2. Peer product adoption',
      monthly: '1. Industry trends 2. Peer product adoption',
    },
    f7: {
      annual: 'Free worth Rs 20,000',
      monthly: 'Free worth Rs 20,000',
    },
    f8: {
      annual: '1.9% (5% Off)',
      monthly: '1.9% (5% Off)',
    },
    f9: {
      annual:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
      monthly:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
    },
    icon: {
      alt: 'Trend Up',
      src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/growth.svg',
    },
    id: 'LD7tbBUZbSMZVp',
    monthlyPrice: 1199,
    title: 'Growth',
  },
  {
    annualPrice: 12999,
    button: {
      label: 'Get Started Now!',
      variant: 'primary',
    },
    description: 'Are you a growing business who requires faster settlement?',
    f1: {
      annual: '\u20b9 75,000',
      monthly: '\u20b9 75,000',
    },
    f2: {
      annual: 'worth \u20b93,00,000',
      monthly: 'worth \u20b92,61,000',
    },
    f3: {
      annual: 'EARLY | T+1',
      monthly: 'EARLY | T+1',
    },
    f4: {
      annual: 'ELITE On Call Resolution',
      monthly: 'ELITE On Call Resolution',
    },
    f5: {
      annual: 'Dedicated Account Manager',
      monthly: 'Dedicated Account Manager',
    },
    f6: {
      annual: '\u2022 Industry trends\n\u2022 Peer product adoption\n\u2022 Success Rate Report',
      monthly: '\u2022 Industry trends\n\u2022 Peer product adoption\n\u2022 Success Rate Report',
    },
    f7: {
      annual: 'Free worth Rs 20,000',
      monthly: 'Free worth Rs 20,000',
    },
    f8: {
      annual: '1.85% (7.5% Off)',
      monthly: '1.85% (7.5% Off)',
    },
    f9: {
      annual:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 $1,000 in credits for Notion Team Plan\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
      monthly:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
    },
    icon: {
      alt: 'Jet',
      src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/rocket.svg',
    },
    id: 'LD7w1SXw34MOVO',
    isRecommended: true,
    monthlyPrice: 1299,
    title: 'Jumbo Jet',
  },
  {
    annualPrice: 19999,
    button: {
      label: 'Buy this plan',
      variant: 'primary',
    },
    description: "Are you a large business, who's looking for faster resolutions?",
    f1: {
      annual: '\u20b9 1,50,000',
      monthly: '\u20b9 1,50,000',
    },
    f2: {
      annual: 'worth \u20b93,50,000',
      monthly: 'worth \u20b93,63,000',
    },
    f3: {
      annual: 'INSTANT | Same Day',
      monthly: 'INSTANT | Same Day',
    },
    f4: {
      annual: 'ELITE On Call Resolution',
      monthly: 'ELITE On Call Resolution',
    },
    f5: {
      annual: 'Dedicated Account Manager',
      monthly: 'Dedicated Account Manager',
    },
    f6: {
      annual:
        '\n\u2022 Industry trends \n\u2022 Peer product adoption \n\u2022 Success Rate Report \n\u2022 1:1 monthly session for Settlement report debrief',
      monthly:
        '\n\u2022 Industry trends \n\u2022 Peer product adoption \n\u2022 Success Rate Report \n\u2022 1:1 monthly session for Settlement report debrief',
    },
    f7: {
      annual: 'Free worth Rs 20,000',
      monthly: 'Free worth Rs 20,000',
    },
    f8: {
      annual: '1.75% (12.5% Off)',
      monthly: '1.75% (12.5% Off)',
    },
    f9: {
      annual:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 $1,000 in credits for Notion Team Plan\n\u2022 Shiprocket 100% cash-back up to Rs. 1000\n\u2022 10% off on recurring billing for AWS, Google Cloud, Azure',
      monthly:
        '\u2022 10% off on Google Workspace on recurring billing\n\u2022 $1,000 in credits for Notion Team Plan\n\u2022 Shiprocket 100% cash-back up to Rs. 1000',
    },
    icon: {
      alt: 'Spaceship',
      src: 'https://betacdn.np.razorpay.in/static/assets/pricing-bundle/spaceship.svg',
    },
    id: 'LD7zJb2z2KL3Rq',
    monthlyPrice: 1999,
    title: 'Spaceship Package',
  },
];

export const finalProps = {
  plans: pricingPlans[0],
  handleCheckoutPayment: jest.fn(),
  featureIdToFeatureCopyMap: {
    f1: 'Receive Payments for FREE per month upto',
    f2: 'Get guaranteed benefits and savings ',
    f3: 'Settlement Period',
    f4: 'Customer Support',
    f5: 'Account Management',
    f6: 'Proprietary Reports',
    f7: 'Exclusive Masterclasses and Consultant Access from Industry Experts',
    f8: 'Transaction Fee post free limit:',
    f9: 'Exclusive offers and Benefits at \u20b90 additional cost',
  },
  closeBottomSheet: jest.fn(),
  showNotificationToast: jest.fn(),
  toggleViewMore: jest.fn(),
  togglePlan: 'monthly' || 'annual',
  isViewMore: false,
};
