import { utmParam } from 'common/utils/constants';
export const SHIPROCKET_DASHBOARD_LINK =
  'https://app.shiprocket.in/register?utm_source=Razorpay&utm_medium=In-Product&utm_campaign=PaymentPages&utm_content=Razorpay-In-product';
export const MAX_ROWS = 50000;
export const MAX_FILE_SIZE = 60457280; // 60MB
export const BATCH_UPLOAD_DOC_URL =
  'https://razorpay.com/docs/payments/payment-pages/batch#batch-file-states';
export const BATCH_TYPE = 'payment_page';
export const BATCH_UPLOAD_MSG = 'To be filled in the batch upload file';
export const FILLED_BY_CUSTOMER = 'To be filled by customer';

export const SEC_REF_ID = 'sec__ref__id';
export const SEC_REF_ID_MAX_ERROR = 'Max 5 Input Field can be added as Secondary Reference ID.';

export const BATCH_UPLOAD_POINTS = [
  'The amount mentioned should be in paise.',
  'The Primary Reference ID and Secondary Reference ID should be unique for each entry.',
  'The number of rows should not exceed 50000.',
];

export const CREATE_PP_DOC_URL = `https://razorpay.com/docs/payments/payment-pages/create/${utmParam}`;
export const CREATE_BATCH_PP_DOC_URL = 'https://razorpay.com/docs/payments/payment-pages/batch';

export const BATCH_PAYMENT_PAGES_BASE_URL = '/paymentpages/batchpaymentpages';
export const NOTIFY_MESSAGE = {
  BATCH_PAYMENT_PAGE: `If Notify ' via SMS' and ' via Email' is selected, Batch Page Link will be sent as soon as the batch is Processed. Incase you wish to send the link later, don't select the Notify 'via SMS' or 'via Email'. The option to send it later will be available under 'Actions' on 'Batch Details'.`,
  PAYMENT_LINK: `Payment Links with SMS and Email will be sent once the batch is created.`,
};
export const MAX_BANNERS_ALLOWED = 5;
export const MAX_ENABLED_BANNERS = 3;
export const MAX_SOCIAL_HANDLE_ALLOWED = 4;

export const SOCIAL_HANDLES = [
  {
    name: 'instagram',
    label: 'Instagram',
    src: 'https://cdn.razorpay.com/static/assets/storefront/instagram.png',
    inputLabel: 'Add your instagram profile link',
    inputPlaceholder: 'https://www.instagram.com/rsubhojit/',
  },
  {
    name: 'facebook',
    label: 'Facebook',
    src: 'https://cdn.razorpay.com/static/assets/storefront/facebook.png',
    inputLabel: 'Add your facebook profile link',
    inputPlaceholder: 'https://www.facebook.com/rsubhojit/',
  },
  {
    name: 'google',
    label: 'Google',
    src: 'https://cdn.razorpay.com/static/assets/storefront/google.png',
    inputLabel: 'Add your google business link',
    inputPlaceholder: 'https://www.google.com/rsubhojit/',
  },
  {
    name: 'youTube',
    label: 'YouTube',
    src: 'https://cdn.razorpay.com/static/assets/storefront/youtube.png',
    inputLabel: 'Add your youtube profile link',
    inputPlaceholder: 'https://www.youtube.com/rsubhojit/',
  },
  {
    name: 'twitter',
    label: 'X (Formerly Twitter)',
    src: 'https://cdn.razorpay.com/static/assets/storefront/x.png',
    inputLabel: 'Add your X (Formerly Twitter) profile link',
    inputPlaceholder: 'https://www.x.com/rsubhojit/',
  },
  {
    name: 'linkedIn',
    label: 'LinkedIn',
    src: 'https://cdn.razorpay.com/static/assets/storefront/linkedin.png',
    inputLabel: 'Add your linkedIn profile link',
    inputPlaceholder: 'https://www.linkedIn.com/rsubhojit/',
  },
  {
    name: 'pinterest',
    label: 'Pinterest',
    src: 'https://cdn.razorpay.com/static/assets/storefront/pinterest.png',
    inputLabel: 'Add your pinterest profile link',
    inputPlaceholder: 'https://www.pinterest.com/rsubhojit/',
  },
  {
    name: 'custom',
    label: 'Custom',
    src: 'https://cdn.razorpay.com/static/assets/storefront/custom.png',
    inputLabel: 'Add your custom profile link',
    inputPlaceholder: 'https://www.custom.com/rsubhojit/',
  },
];
