export const ROUTES = {
  DASHBOARD: '/app/dashboard',
  OVERVIEW_DASHBOARD: '/app/billme/',
  BILLS: '/app/billme/bills/',
  FEEDBACK: '/app/billme/feedback',
  PROFILING: '/app/billme/consumer-profiling',
  SEGMENTATION: '/app/billme/consumer-segmentation',
  COMMUNICATION_CAMPAIGNS: '/app/billme/communication-campaigns',
  BILLS_CAMPAIGNS: '/app/billme/bill-campaigns',
  AUTO_ENGAGEMENT: '/app/billme/auto-engagement',
  SURVEY_MANAGEMENT: '/app/billme/survey-management',
  COUPON_MANAGEMENT: '/app/billme/coupon-management',
  MEDIA_BANK: '/app/billme/media-bank',
  USAGE_AND_INVOICES: '/app/billme/usage-and-invoices',
  SETTINGS: '/app/billme/settings',
};

export const IFRAME_PATH_INFO = [
  {
    linkTitle: 'Feedback and Complaints',
    pathname: ROUTES.FEEDBACK,
    iframePath: '/feedback/campaigns',
  },
  {
    linkTitle: 'Profiling',
    pathname: ROUTES.PROFILING,
    iframePath: '/consumer-profiling',
  },
  {
    linkTitle: 'Segmentation',
    pathname: ROUTES.SEGMENTATION,
    iframePath: '/segment',
  },
  {
    linkTitle: 'Communication',
    pathname: ROUTES.COMMUNICATION_CAMPAIGNS,
    iframePath: '/auto-engage/sms',
  },
  {
    linkTitle: 'Bills',
    pathname: ROUTES.BILLS_CAMPAIGNS,
    iframePath: '/auto-engage/bannerInBill',
  },
  {
    linkTitle: 'Auto-Engagement',
    pathname: ROUTES.AUTO_ENGAGEMENT,
    iframePath: '/journey',
  },
  {
    linkTitle: 'Surveys',
    pathname: ROUTES.SURVEY_MANAGEMENT,
    iframePath: '/auto-engage/surveys',
  },
  {
    linkTitle: 'Coupons',
    pathname: ROUTES.COUPON_MANAGEMENT,
    iframePath: '/auto-engage/coupons',
  },
  {
    linkTitle: 'Media Bank',
    pathname: ROUTES.MEDIA_BANK,
    iframePath: '/auto-engage/uploadedData',
  },
  {
    linkTitle: 'Usage & Invoice',
    pathname: ROUTES.USAGE_AND_INVOICES,
    iframePath: '/usage-and-invoices',
  },
  {
    linkTitle: 'Settings',
    pathname: ROUTES.SETTINGS,
    iframePath: '/settings',
  },
];
