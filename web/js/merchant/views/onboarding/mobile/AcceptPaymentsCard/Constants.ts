export const INTERNATIONAL_BLACKLIST = {
  title: 'Accept Live Payments!',
  description:
    'You can start accepting domestic payments. International payments are currently not supported for your business model.',
};

export const INTERNATIONAL_FLOW = {
  unreg: {
    l1_submitted: {
      title: 'Accept Live Payments!',
      description: 'You can start accepting domestic payments.',
    },
    account_activated: {
      title: 'Accept Live Payments!',
      description:
        'You can start accepting domestic payments. You can integrate PayPal to enable international payments.',
    },
  },
  af_wl_iaf_gl: {
    l1_submitted: {
      title: 'Live Payments Enabled!',
      description:
        'You can accept domestic payments. To enable international payments complete activation.',
    },
    account_activated: {
      title: 'Accept International Payments!',
      description: 'You can now enable international payments.',
    },
  },
  af_wl_iaf_wl: {
    l1_submitted: {
      has_website: {
        title: 'Accept Live Payments!',
        description: 'You can accept domestic and international payments via payment gateway.',
      },
      no_website: {
        title: 'Accept Live Payments!',
        description:
          'You can accept domestic payments. To accept international payments update your website.',
      },
    },
    account_activated: {
      has_website: {
        title: 'Accept International Payments!',
        description:
          'You can accept international payments via payment gateway and can enable inernational payments for other products too.',
      },
      no_website: {
        title: 'Accept Live Payments!',
        description:
          'You can accept domestic payments. To accept international payments update your website.',
      },
    },
  },
  af_gl_iaf_gl: {
    title: 'Accept live payments!',
    description: 'You can start accepting domestic payments and can enable international payments.',
  },
};

export const INTERNATIONAL_REQUEST = {
  in_review: {
    title: 'International payments request under review',
    description: 'Your request for international payments is under review.',
  },
  rejected: {
    title: 'International payments request rejected',
    description: 'Your request for international payments was rejected.',
  },
};

export const PAYMENT_ESCALATION = {
  breach:
    'You have reached the payments limit and your payments have been paused. Please complete your KYC to extend the limit.',
  not_breach: 'You can accept payment upto ₹15000 INR. To extend the limit complete KYC',
};
