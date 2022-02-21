export const STATUS = {
  /** Not eligible, not waitlisted, not delisted once */
  NOT_ELIGIBLE_WAITLISTED_DELISTED: 'NOT_ELIGIBLE_WAITLISTED_DELISTED',
  /** Not eligible, yes waitlisted, yes delisted once */
  NOT_ELIGIBLE_YES_WAITLISTED_DELISTED: 'NOT_ELIGIBLE_YES_WAITLISTED_DELISTED',
  /** Not eligible, yes waitlisted, no delisted once */
  NOT_ELIGIBLE_DELISTED_YES_WAITLISTED: 'NOT_ELIGIBLE_DELISTED_YES_WAITLISTED',
  /** yes eligible, yes opted out */
  YES_ELIGIBLE_OPTED_OUT: 'YES_ELIGIBLE_OPTED_OUT',
  /** yes eligible, live */
  YES_ELIGIBLE_LIVE: 'YES_ELIGIBLE_LIVE',
};

export const pageData = {
  [STATUS.NOT_ELIGIBLE_WAITLISTED_DELISTED]: {
    version: '5',
    sections: {
      main: {
        id: 'main',
        type: 'details',
        title: 'Join the waitlist and be a Razorpay Trusted Business soon!',
        subtitle: 'What does the badge stand for:',
        details: [
          'It is a sign of quality and trust',
          'It shows your commitment to serving your customers',
          'It will be 100% free of cost on checkout once live',
        ],
        imgSrc: `https://cdn.razorpay.com/static/assets/trustedbadge/${STATUS.NOT_ELIGIBLE_WAITLISTED_DELISTED.toLowerCase()}.svg`,
        subComponent: ['joinWaitlist'],
      },
      requirements: {
        subComponent: ['wantToKnow', 'joinWaitlist'],
      },
    },
    order: ['main', 'growth', 'requirements'],
  },
  [STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED]: {
    version: '4',
    sections: {
      main: {
        id: 'main',
        type: 'details',
        title: 'Razorpay Trusted Business badge is not available for you currently',
        subtitle: 'What does the badge stand for:',
        details: [
          'It is a sign of quality and trust',
          'It shows your commitment to serving your customers',
          'It will be 100% free of cost on checkout once live',
        ],
        imgSrc: `https://cdn.razorpay.com/static/assets/trustedbadge/${STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED.toLowerCase()}.svg`,
        subComponent: ['divider', 'notAvailable', 'info'],
      },
      requirements: {
        subComponent: ['wantToKnow', 'notAvailable'],
      },
      info: {
        type: 'sub-text',
        text:
          'Remember, you don’t need to apply for the Razorpay Trusted Business badge. If you meet the eligibility criteria as determined by Razorpay, your payment checkout page will start displaying the badge automatically.',
        showKnowMore: true,
      },
    },
    order: ['main', 'requirements', 'growth'],
  },
  [STATUS.NOT_ELIGIBLE_DELISTED_YES_WAITLISTED]: {
    version: '3',
    sections: {
      main: {
        id: 'main',
        type: 'details',
        title: 'You are in the waitlist to become a Razorpay Trusted Business!',
        subtitle: 'What does the badge stand for:',
        details: [
          'It is a sign of quality and trust',
          'It shows your commitment to serving your customers',
          'It will be 100% free of cost on checkout once live',
        ],
        imgSrc: `https://cdn.razorpay.com/static/assets/trustedbadge/${STATUS.NOT_ELIGIBLE_DELISTED_YES_WAITLISTED.toLowerCase()}.svg`,
        subComponent: ['divider', 'joinedWaitlist', 'info'],
      },
      requirements: {
        subComponent: ['wantToKnow', 'joinedWaitlist'],
      },
      info: {
        type: 'sub-text',
        text:
          'We are gradually rolling out the program. Once you meet the eligibility criteria as determined by Razorpay, the badge will automatically be activated on your checkout. Please check back later.',
      },
    },
    order: ['main', 'growth', 'requirements'],
  },
  [STATUS.YES_ELIGIBLE_OPTED_OUT]: {
    version: '2',
    sections: {
      main: {
        id: 'main',
        type: 'details',
        title: 'You have opted out of the Razorpay Trusted Business program',
        subtitle:
          'The badge is not active on your checkout. See what the badge can do for your business:',
        details: [
          'Increase order conversion rate and retention by upto 5%',
          'Build credibility for your business',
          'Reduce dependency on cash on delivery',
        ],
        imgSrc: `https://cdn.razorpay.com/static/assets/trustedbadge/${STATUS.YES_ELIGIBLE_OPTED_OUT.toLowerCase()}.svg`,
        subComponent: ['info', 'activateBadge'],
      },
      requirements: {
        subComponent: ['wantToKnow'],
      },
      info: {
        type: 'sub-text',
        text:
          'Your payment checkout page can start displaying the badge at the click of the activate button. <a href="https://razorpay.com/docs/payment-gateway/dashboard-guide/trusted-badge/" rel="noreferrer noopener" target="_blank">Know More</a>',
      },
    },
    order: ['main', 'requirements'],
  },
  [STATUS.YES_ELIGIBLE_LIVE]: {
    version: '1',
    sections: {
      main: {
        id: 'main',
        type: 'details',
        title: 'Razorpay Trusted Business badge is live on your checkout!',
        subtitle: 'What does the badge stand for:',
        details: [
          'It is a sign of quality and trust',
          'It shows your commitment to serving your customers',
        ],
        imgSrc: `https://cdn.razorpay.com/static/assets/trustedbadge/rtb_${STATUS.YES_ELIGIBLE_LIVE.toLowerCase()}.svg`,
        subComponent: ['info', 'optOut'],
      },
      requirements: {
        title: 'How to remain eligible for the badge?',
        subComponent: ['reserves', 'wantToKnow'],
      },
      info: {
        type: 'sub-text',
        text: '100% free of cost on checkout',
      },
      reserves: {
        type: 'sub-text',
        text:
          'Please note, Razorpay reserves the right to revoke the badge should you fail to comply with the program’s requirements.',
      },
      wantToKnow: {
        // override default
        text: 'Have any more queries about the program?',
      },
      optOut: {
        className: 'mt-1',
        type: 'sub-text',
        text: 'Not Interested?',
        optOut: true,
      },
    },
    order: ['main', 'growth', 'requirements'],
  },
  /** render common section after render sections related to particular type */
  components: {
    growth: {
      id: 'growth',
      title: 'How will the badge help your business?',
      type: 'growth',
      features: [
        {
          icon: 'https://cdn.razorpay.com/static/assets/trustedbadge/boost_feature.svg',
          title: 'Boosts payment volume',
          desc: 'Businesses see upto 5% increase in order conversion rate and retention',
        },
        {
          icon: 'https://cdn.razorpay.com/static/assets/trustedbadge/build-brand_feature.svg',
          title: 'Builds brand credibility',
          desc:
            'Unlock growth and attract new customers for your business by building love for you brand',
        },
        {
          icon: 'https://cdn.razorpay.com/static/assets/trustedbadge/cod_feature.svg',
          title: 'Reduces dependency on COD',
          desc:
            'As customers start trusting your brand, they increasingly pay via online payment modes',
        },
      ],
    },
    requirements: {
      id: 'requirements',
      headClass: 'green',
      type: 'details',
      className: 'mt-6',
      title: 'Eligibility Criteria for the badge',
      imgSrc: 'https://cdn.razorpay.com/static/assets/trustedbadge/rtb_eligibility.svg',
      details: [
        'Maintain excellent customer service levels by <strong>resolving escalations and disputes</strong> on time',
        'Keep using Razorpay for processing your <strong>online payments</strong>',
        'Regularly pass our risk detection systems and other <strong>proprietary checks</strong>',
      ],
    },
    wantToKnow: {
      type: 'sub-text',
      text:
        'You must complete KYC verification and update all your business and personal details to be eligible for the badge.',
      showDocTnCLink: true,
    },
    joinWaitlist: {
      type: 'button',
      label: 'Join the waitlist',
      helperText: 'Be a part of our elite trusted business community',
      action: 'join-waitlist',
    },
    activateBadge: {
      type: 'button',
      label: 'Activate your badge',
      helperText: 'Be a part of our elite trusted business community',
      action: 'activate-badge',
    },
    notAvailable: {
      type: 'text-icon',
      icon: 'https://cdn.razorpay.com/static/assets/trustedbadge/rtb_not_avaiilable.svg',
      text: 'Not available for you at the moment',
    },
    joinedWaitlist: {
      type: 'text-icon',
      icon: 'https://cdn.razorpay.com/static/assets/trustedbadge/rtb_waitlist.svg',
      text: 'You are in the waitlist',
    },
    divider: {
      type: 'divider',
    },
  },
};

export const optOutDialog = {
  confirm: {
    title: 'Are you sure you want to opt out?',
    body:
      'Businesses like you see upto 5% increase in conversion by displaying the badge on their Checkout. If you proceed to opt-out, the Razorpay Trusted Business badge will be removed from your checkout.',
    action: [
      {
        label: 'No, don’t opt out',
        action: 'close-modal',
      },
      {
        type: 'primary',
        label: 'Yes, opt out',
        action: 'opt-out',
      },
    ],
  },
  postConfirm: {
    title: 'Razorpay Trusted Business badge has been removed from your checkout page',
    body:
      'We are sorry to see you go from the program. Do tell us how we can improve the experience of being a Razorpay Trusted Business for you.',
    link: {
      text: 'Tell us how we can improve',
      href: 'https://razorpay.typeform.com/to/Q0KKcFDu',
    },
    action: [
      {
        type: 'primary',
        label: 'Proceed to dashboard',
        action: 'close-modal',
      },
    ],
  },
};
