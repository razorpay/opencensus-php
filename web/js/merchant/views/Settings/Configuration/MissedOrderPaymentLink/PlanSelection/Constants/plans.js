export const MOPL_PLANS = [
  {
    features: {
      channels: {
        email: true,
        sms: true,
        whatsapp: true,
      },
      analytics: {
        channel_conversions: true,
        impact_analysis: true,
        order_revival_rates: true,
      },
    },
    id: 'config_KgadjlWfjB5W0c',
    name: 'pro',
    price: 300,
    price_placeholder: '--',
    disabled: true,
  },
  {
    features: {
      channels: {
        email: true,
        sms: true,
        whatsapp: false,
      },
      analytics: {
        channel_conversions: false,
        impact_analysis: true,
        order_revival_rates: true,
      },
    },
    id: 'config_KgafTJm0aj2C3Q',
    name: 'basic',
    price: 100,
    price_placeholder: 'Pay',
    disabled: false,
  },
];
