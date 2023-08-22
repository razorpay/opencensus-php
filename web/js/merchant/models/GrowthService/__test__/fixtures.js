const tracking_id = 'test';
const extUrlData = [
  {
    type: 'url',
    url: 'https://dashboard.razorpay.com/app/dashboard',
  },
];

const intUrlData = [
  {
    type: 'url',
    url: '/dashboard',
  },
];

const sfData = [
  {
    properties: {
      Campaign_ID: 'feb23_payroll_homepage_eo',
      Event_Type: 'Opfin',
      product_name: 'Current_Account',
    },
    type: 'salesforce_event',
  },
];

const modaldata = (type, variant) => [
  {
    sub_asset: {
      id: 'LJR3V48ew9ych1',
      type,
      variant,
    },
    type: 'template',
  },
];

const empty_tracking_obj = {
  trackingID: '',
  campaign: undefined,
  campaign_description: undefined,
  version: undefined,
  version_description: undefined,
  campaign_id: undefined,
  dynamic_asset_name: undefined,
  sub_campaign_id: undefined,
  target_metric: undefined,
  product_feature: undefined,
  growth_event_type: undefined,
  template_id: undefined,
  channel_id: undefined,
  asset: undefined,
};

const card_id = 'test_id';
const eventName = 'test_eventName';
const trackingData = {
  campaign: 'Test Campaign',
  campaign_description: 'Test Campaign desc',
  sub_campaign: 'Test sub_campaign',
  sub_campaign_description: 'Test sub_campaign desc',
  campaign_id: 'IF7Nec84wWYRqO',
  sub_campaign_id: 'IF7Uk0WuK32Eh8',
  template_id: 'IF7Uk0WuK32E45',
  asset: 'BANNER',
  dynamic_asset_name: undefined,
  channel_id: 'IF7Uk0WuK32E46',
  tags: {
    product_feature: 'RazorpayX Current Account',
  },
};

const expectedTrackingObj = {
  trackingID: 'test_id',
  campaign: 'Test Campaign',
  campaign_description: 'Test Campaign desc',
  version: 'Test sub_campaign',
  version_description: 'Test sub_campaign desc',
  campaign_id: 'IF7Nec84wWYRqO',
  sub_campaign_id: 'IF7Uk0WuK32Eh8',
  target_metric: undefined,
  product_feature: 'RazorpayX Current Account',
  growth_event_type: undefined,
  dynamic_asset_name: undefined,
  template_id: 'IF7Uk0WuK32E45',
  channel_id: 'IF7Uk0WuK32E46',
  asset: 'BANNER',
};

const banners = [
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'LSBnYWR1iE2tIm',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Know more',
        style: 'normal',
        type: 'button',
      },
    ],
    content: {
      description: 'Improved Razorpay 4.4.3 for WooCommerce with bug fixes and new features',
      type: 'normal',
    },
    dismissible: true,
    id: 'WooC_4.4.3_upgrade_MAR1623_BANNER_LSBnYX6a6X2djb',
    override_priority: false,
    theme: 'primary',
    title: 'Razorpay 4.4.3 for Woocommerce test1',
  },
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'LSBnYWR1iE2tIm',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Know more',
        style: 'normal',
        type: 'button',
      },
    ],
    content: {
      description: 'Improved Razorpay 4.4.3 for WooCommerce with bug fixes and new features',
      type: 'normal',
    },
    dismissible: true,
    id: 'WooC_4.4.3_upgrade_MAR1623_BANNER_LSBnYX6a6X2djx',
    override_priority: true,
    theme: 'primary',
    title: 'Razorpay 4.4.3 for Woocommerce test2',
  },
];
const sorted_banners = [
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'LSBnYWR1iE2tIm',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Know more',
        style: 'normal',
        type: 'button',
      },
    ],
    content: {
      description: 'Improved Razorpay 4.4.3 for WooCommerce with bug fixes and new features',
      type: 'normal',
    },
    dismissible: true,
    id: 'WooC_4.4.3_upgrade_MAR1623_BANNER_LSBnYX6a6X2djx',
    override_priority: true,
    theme: 'primary',
    title: 'Razorpay 4.4.3 for Woocommerce test2',
  },
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'LSBnYWR1iE2tIm',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Know more',
        style: 'normal',
        type: 'button',
      },
    ],
    content: {
      description: 'Improved Razorpay 4.4.3 for WooCommerce with bug fixes and new features',
      type: 'normal',
    },
    dismissible: true,
    id: 'WooC_4.4.3_upgrade_MAR1623_BANNER_LSBnYX6a6X2djb',
    override_priority: false,
    theme: 'primary',
    title: 'Razorpay 4.4.3 for Woocommerce test1',
  },
];

const announcement = [
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'L61TlRq2gnMQaY',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Claim This Offer',
        style: 'normal',
        type: 'button',
      },
    ],
    description: 'Vendor Payments + Canva Pro + 15% Discount on Payment Gateway Pricing - for You.',
    end_ts: 1676795836,
    icon: 'CDN_URL_PREFIX/growth/L60zu1xQGIjIrE/RazorpayX (1).png',
    id: 'FY22Q4_Nitro_P1_PremiumUpgrade_Jan23_JAN1923_ANNOUNCEMENT_L61TlSnV3PCEsc',
    start_ts: 1674117436,
    title: 'Congratulations! Avail a Premium Upgrade just for you. test1',
  },
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'L61TlRq2gnMQaY',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Claim This Offer',
        style: 'normal',
        type: 'button',
      },
    ],
    description: 'Vendor Payments + Canva Pro + 15% Discount on Payment Gateway Pricing - for You.',
    end_ts: 1676795836,
    icon: 'CDN_URL_PREFIX/growth/L60zu1xQGIjIrE/RazorpayX (1).png',
    id: 'FY22Q4_Nitro_P1_PremiumUpgrade_Jan23_JAN1923_ANNOUNCEMENT_L61TlSnV3PCEsd',
    start_ts: 1675117436,
    title: 'Congratulations! Avail a Premium Upgrade just for you. test2',
  },
];
const sorted_announcement = [
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'L61TlRq2gnMQaY',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Claim This Offer',
        style: 'normal',
        type: 'button',
      },
    ],
    description: 'Vendor Payments + Canva Pro + 15% Discount on Payment Gateway Pricing - for You.',
    end_ts: 1676795836,
    icon: 'CDN_URL_PREFIX/growth/L60zu1xQGIjIrE/RazorpayX (1).png',
    id: 'FY22Q4_Nitro_P1_PremiumUpgrade_Jan23_JAN1923_ANNOUNCEMENT_L61TlSnV3PCEsd',
    start_ts: 1675117436,
    title: 'Congratulations! Avail a Premium Upgrade just for you. test2',
  },
  {
    buttons: [
      {
        handler: [
          {
            sub_asset: {
              id: 'L61TlRq2gnMQaY',
              type: 'MODAL',
              variant: 'default',
            },
            type: 'template',
          },
        ],
        id: 'default',
        label: 'Claim This Offer',
        style: 'normal',
        type: 'button',
      },
    ],
    description: 'Vendor Payments + Canva Pro + 15% Discount on Payment Gateway Pricing - for You.',
    end_ts: 1676795836,
    icon: 'CDN_URL_PREFIX/growth/L60zu1xQGIjIrE/RazorpayX (1).png',
    id: 'FY22Q4_Nitro_P1_PremiumUpgrade_Jan23_JAN1923_ANNOUNCEMENT_L61TlSnV3PCEsc',
    start_ts: 1674117436,
    title: 'Congratulations! Avail a Premium Upgrade just for you. test1',
  },
];

export {
  extUrlData,
  intUrlData,
  sfData,
  modaldata,
  tracking_id,
  empty_tracking_obj,
  card_id,
  eventName,
  trackingData,
  expectedTrackingObj,
  banners,
  sorted_banners,
  announcement,
  sorted_announcement,
};
