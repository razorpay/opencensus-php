import lazy from 'merchant/routes/LazyLoader';

const ExpiredCouponsTab = lazy(() =>
  import(
    /* webpackChunkName: 'MagicCouponEngineExpiredCouponsTab' */ 'merchant/views/MagicCheckout/CouponEngine/pages/ExpiredCouponsTab'
  ),
);
const ActiveCouponsTab = lazy(() =>
  /* webpackChunkName: 'MagicCouponEngineActiveCouponsTab' */ import(
    'merchant/views/MagicCheckout/CouponEngine/pages/ActiveCouponsTab'
  ),
);
const AllCouponsTab = lazy(() =>
  /* webpackChunkName: 'MagicCouponEngineAllCouponsTab' */ import(
    'merchant/views/MagicCheckout/CouponEngine/pages/AllCouponsTab'
  ),
);

const EnableCouponsTab = lazy(() =>
  /* webpackChunkName: 'MagicCouponEngineEnableCouponsTab' */ import(
    'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponsTab'
  ),
);

export const getNavItems = (syncExperiment = false) => {
  const navItems = [
    {
      id: 'allCoupons',
      title: 'All coupons',
      component: <AllCouponsTab />,
    },
    {
      id: 'activeCoupons',
      title: 'Active coupons',
      component: <ActiveCouponsTab />,
    },
    {
      id: 'expiredCoupons',
      title: 'Expired coupons',
      component: <ExpiredCouponsTab />,
    },
  ];

  if (syncExperiment) {
    navItems.unshift({
      id: 'setup',
      title: 'Setup',
      component: <EnableCouponsTab />,
    });
  }

  return navItems;
};

export const COUPON_TYPES = [
  { label: 'All', name: '' },
  { label: 'Amount Off Product', name: 'amount_off_products' },
  { label: 'Amount Off Order', name: 'amount_off_order' },
  { label: 'Buy X Get Y', name: 'buyx_gety' },
  { label: 'Bulk Order', name: 'bulk_order' },
];

export const COUPON_STATUS = [
  { label: 'All', name: '' },
  { label: 'Created', name: 'created' },
  { label: 'Active', name: 'active' },
  { label: 'Inactive', name: 'in_active' },
  { label: 'Expired', name: 'expired' },
  { label: 'Published', name: 'published' },
];

export const COUPON_DISPLAY = [
  { label: 'All', name: 'all' },
  { label: 'Yes', name: 'yes' },
  { label: 'No', name: 'no' },
];

export const COUPON_SOURCES = [
  { label: 'All', name: 'all' },
  { label: 'Coupon Engine', name: 'ce' },
  { label: 'Shopify', name: 'shopify' },
];

export const SORT_BY = [
  { label: 'Date (From Newest)', name: 'date-desc' },
  { label: 'Date (From Oldest)', name: 'date-asc' },
];

export const AVAILABLE_COUPON_TYPES = [
  {
    couponName: 'Amount discounted on orders',
    couponDesc: 'Order discount',
    id: 1,
    type: 'amount_off_order',
  },
  {
    couponName: 'Amount discounted on products',
    couponDesc: 'Product discount',
    id: 2,
    type: 'amount_off_products',
  },
  {
    couponName: 'Buy X Get Y',
    couponDesc: 'Product discount',
    id: 3,
    type: 'buyx_gety',
  },
  {
    couponName: 'Bulk discount',
    couponDesc: 'Bundle and product discount',
    id: 4,
    type: 'bulk_order',
  },
];

export const DISPLAY_MESSAGES_FOR_UFH_MODAL = {
  process: 'The file is being processed. Please wait as this may take some time.',
  success: 'The file has been processed successfully.',
  error: 'There was an error while processing the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

export const SAMPLE_FILE_URL_FOR_EMAIL =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_customer_details_file.csv';

export const SAMPLE_FILE_URL_FOR_MOBILE =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_customer_mobile_details_file.csv';

export const COUNT = [
  { label: '25', name: 25 },
  { label: '20', name: 20 },
  { label: '15', name: 15 },
  { label: '10', name: 10 },
  { label: '5', name: 5 },
];
