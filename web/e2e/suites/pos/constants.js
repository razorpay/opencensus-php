export const POS_URL = `https://dashboard${
  process.env.DEVSTACK_LABEL ? `-${process.env.DEVSTACK_LABEL}` : ''
}.dev.razorpay.in/app/pos`;

export const MAIN_BANNER_TEXT_CONTENT = [
  'Android Smart POS',
  'All-in-one POS to support all your payment needs',
  'Accept card and UPI payments',
  'Uninterrupted connectivity over wifi / sim',
  'Instant audio confirmations',
  'In-built printer for printing charges slips',
];

export const DEVICE_CODES = {
  androidSmartPos: 'a910',
  androidMiniPos: 'a50',
  mobilePos: 'd180',
};

export const URLS = {
  ANDROID_SMART_POS: `${POS_URL}/catalog/${DEVICE_CODES.androidSmartPos}`,
};

export const PDP_CONTENT = {
  [DEVICE_CODES.androidSmartPos]: {
    title: 'Android Smart POS',
    subtitle:
      'Android 6.0 or 7.0 Powered | 5” HD display | PCI 6 SRED Certified | Quad-core Cortex A7 Processor | Fast Thermal Printer',
  },
  [DEVICE_CODES.androidMiniPos]: {
    title: 'Android Smart Mini POS',
    subtitle:
      'Android 8.1 or 10 Powered | Ultra slim | 4.5” HD Display | PCI PTS 5.x (Android 8.1) or 6.x (Android 10) SRED Certified | Inbuilt GPS',
  },
  [DEVICE_CODES.mobilePos]: {
    title: 'Mobile POS (mPOS)',
    subtitle:
      'Android and iOS compatible | PCI PTS 5.x Certified | USB Port for high-speed data transmission | 128 x 64 pixels LCD Display',
  },
};
