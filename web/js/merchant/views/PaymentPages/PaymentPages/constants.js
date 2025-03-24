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

export const PLATFORM_NAMES = {
  INSTAGRAM: 'instagram',
  FACEBOOK: 'facebook',
  GOOGLE: 'google',
  YOUTUBE: 'youTube',
  X: 'twitter',
  LINKEDIN: 'linkedIn',
  PINTEREST: 'pinterest',
  CUSTOM: 'custom',
};

export const SOCIAL_HANDLES = [
  {
    name: PLATFORM_NAMES.INSTAGRAM,
    label: 'Instagram',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2enzzenkbxdxm.jpeg',
    inputLabel: 'Add your instagram profile link',
    inputPlaceholder: 'https://www.instagram.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.FACEBOOK,
    label: 'Facebook',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2ehtofeewwary.jpeg',
    inputLabel: 'Add your facebook profile link',
    inputPlaceholder: 'https://www.facebook.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.GOOGLE,
    label: 'Google',
    src: 'https://s3.ap-south-1.amazonaws.com//rzp-prod-merchant-assets/payment-link/description/q2ekuntwdqqhdv.jpeg',
    inputLabel: 'Add your google business link',
    inputPlaceholder: 'https://www.google.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.YOUTUBE,
    label: 'YouTube',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2eplcgd2xuhsg.jpeg',
    inputLabel: 'Add your youtube profile link',
    inputPlaceholder: 'https://www.youtube.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.X,
    label: 'X (Formerly Twitter)',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2epffgh2ik1iu.jpeg',
    inputLabel: 'Add your X (Formerly Twitter) profile link',
    inputPlaceholder: 'https://www.x.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.LINKEDIN,
    label: 'LinkedIn',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2eo5k6vkp8yfg.jpeg',
    inputLabel: 'Add your linkedIn profile link',
    inputPlaceholder: 'https://www.linkedIn.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.PINTEREST,
    label: 'Pinterest',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2epan4hc2sibw.jpeg',
    inputLabel: 'Add your pinterest profile link',
    inputPlaceholder: 'https://www.pinterest.com/rsubhojit/',
  },
  {
    name: PLATFORM_NAMES.CUSTOM,
    label: 'Custom',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2egwezan5yiqv.jpeg',
    inputLabel: 'Add your custom profile link',
    inputPlaceholder: 'https://www.custom.com/rsubhojit/',
  },
];

export const SOCIAL_PATTERNS = {
  // Instagram: Handles usernames with letters, numbers, underscores, and periods (not consecutive)
  [PLATFORM_NAMES.INSTAGRAM]:
    /^(?:https?:)?\/\/(?:www\.)?(?:instagram\.com|instagr\.am)\/(?<username>[A-Za-z0-9_](?:(?:[A-Za-z0-9_]|(?:\.(?!\.))){0,28}(?:[A-Za-z0-9_]))?)/,

  // Facebook: Multiple patterns for different URL formats
  [PLATFORM_NAMES.FACEBOOK]: [
    // Profile link (e.g., https://www.facebook.com/username)
    /^(?:https?:)?\/\/(?:www\.)?(?:facebook|fb)\.com\/(?<profile>(?![A-z]+\.php)(?!marketplace|gaming|watch|me|messages|help|search|groups)[A-z0-9_\-\.]+)\/?/,
    // Facebook user ID (e.g., https://www.facebook.com/profile.php?id=123456789)
    /^(?:https?:)?\/\/(?:www\.)facebook.com\/(?:profile.php\?id=)?(?<id>[0-9]+)/,
    // Facebook page link (e.g., https://www.facebook.com/pages/PageName/123456789)
    /^(?:https?:)?\/\/(?:www\.)?facebook\.com\/pages\/[^\/]+\/(?<page_id>\d+)\/?$/,
    // Facebook group link (e.g., https://www.facebook.com/groups/123456789)
    /^(?:https?:)?\/\/(?:www\.)?facebook\.com\/groups\/(?<group_id>[^\/]+)\/?$/,
  ],

  // YouTube: Various formats for channels, users, and videos
  [PLATFORM_NAMES.YOUTUBE]: [
    // Channel link (e.g., https://www.youtube.com/channel/UC...)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/channel\/(?<id>[A-z0-9-\_]+)\/?/,
    // User link (e.g., https://www.youtube.com/user/username)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/user\/(?<username>[A-z0-9]+)\/?/,
    // Video link (e.g., https://youtu.be/VIDEO_ID or https://www.youtube.com/watch?v=VIDEO_ID)
    /^(?:https?:)?\/\/(?:(?:www\.)?youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)(?<id>[A-z0-9\-\_]+)/,
    // Custom channel name (e.g., https://www.youtube.com/c/channelname)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/c\/(?<custom_name>[A-z0-9-_]+)\/?/,
    // Handle format (e.g., https://www.youtube.com/@username)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/@(?<handle>[A-z0-9-_]+)\/?/,
  ],

  // Twitter/X: Profile and tweet links
  [PLATFORM_NAMES.X]: [
    // Tweet link (e.g., https://twitter.com/username/status/123456789)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?<username>[A-z0-9_]+)\/status\/(?<tweet_id>[0-9]+)\/?/,
    // Profile link (e.g., https://twitter.com/username)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?!home|share|privacy|tos)(?<username>[A-z0-9_]+)\/?/,
    // X.com links (new Twitter domain)
    /^(?:https?:)?\/\/(?:[A-z]+\.)?x\.com\/@?(?!home|share|privacy|tos)(?<username>[A-z0-9_]+)\/?/,
    // X.com tweet link
    /^(?:https?:)?\/\/(?:[A-z]+\.)?x\.com\/@?(?<username>[A-z0-9_]+)\/status\/(?<tweet_id>[0-9]+)\/?/,
  ],

  // LinkedIn: Various link formats for profiles, companies, etc.
  [PLATFORM_NAMES.LINKEDIN]: [
    // Company or school profile (e.g., https://www.linkedin.com/company/company-name)
    /^(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/(?<company_type>(company)|(school))\/(?<company_permalink>[A-z0-9-À-ÿ\.]+)\/?/,
    // Activity link (e.g., https://www.linkedin.com/feed/update/urn:li:activity:123456789/)
    /^(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/feed\/update\/urn:li:activity:(?<activity_id>[0-9]+)\/?/,
    // Profile link (e.g., https://www.linkedin.com/in/username)
    /^(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/in\/(?<permalink>[\w\-\_À-ÿ%]+)\/?/,
    // Public profile link (e.g., https://www.linkedin.com/pub/username)
    /^(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/pub\/(?<permalink_pub>[A-z0-9_-]+)(?:\/[A-z0-9]+){3}\/?/,
    // Showcase pages (e.g., https://www.linkedin.com/showcase/product-name)
    /^(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/showcase\/(?<showcase_permalink>[A-z0-9-À-ÿ\.]+)\/?/,
  ],

  // Pinterest: Profile and board links
  [PLATFORM_NAMES.PINTEREST]: [
    // Profile link (e.g., https://www.pinterest.com/username/)
    /^(?:https?:)?\/\/(?:www\.)?pinterest\.com\/(?<username>[A-Za-z0-9_]+)\/?$/,
    // Board link (e.g., https://www.pinterest.com/username/boardname/)
    /^(?:https?:)?\/\/(?:www\.)?pinterest\.com\/(?<username>[A-Za-z0-9_]+)\/(?<board_name>[A-Za-z0-9_-]+)\/?$/,
    // Pin link (e.g., https://www.pinterest.com/pin/123456789/)
    /^(?:https?:)?\/\/(?:www\.)?pinterest\.com\/pin\/(?<pin_id>[0-9]+)\/?$/,
  ],

  // Google: Various formats for profiles (mostly legacy Google+ links)
  [PLATFORM_NAMES.GOOGLE]: [
    // Google Plus profile ID link (e.g., https://plus.google.com/1234567890)
    /^(?:https?:\/\/)?plus\.google\.com\/(\d+)\/?$/,

    // Google Plus username link (e.g., https://plus.google.com/+rsubhojit)
    /^(?:https?:)?\/\/plus\.google\.com\/\+(?<username>[A-Za-z0-9+]+)\/?/,

    // Google Profile link (either profile ID or username)
    /^(?:https?:)?\/\/plus\.google\.com\/(?<id>[0-9]{21})|^(?:https?:)?\/\/plus\.google\.com\/\+(?<username>[A-Za-z0-9+]+)\/?/,

    // Google Maps Business Profile link (e.g., https://www.google.com/maps/place/Business+Name/@latitude,longitude,...)
    /^(?:https?:)?\/\/www\.google\.com\/maps\/place\/[A-Za-z0-9\s\+\-]+\/?(@?[0-9\-\.]+,[0-9\-\.]+)(?:\/|$)/,

    // General Google Profile link (e.g., https://www.google.com/profiles/username)
    /^(?:https?:)?\/\/www\.google\.com\/profiles\/(?<username>[A-Za-z0-9_\-]+)\/?$/,

    // General google.com/anything (any path under www.google.com)
    /^(?:https?:)?\/\/www\.google\.com\/.*/, // Matches any URL starting with www.google.com/
  ],
};

export const CROPPER_ASPECT_RATIOS_DESKTOP = {
  width: 984,
  height: 200,
}

export const CROPPER_ASPECT_RATIOS_MOBILE = {
  width: 360,
  height: 140,
}
