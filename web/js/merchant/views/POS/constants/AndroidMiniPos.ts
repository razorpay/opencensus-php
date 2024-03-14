import CartImage from 'assets/pos/product-description/android-mini-pos/cart-img.webp';
import GroupIcon from 'assets/pos/product-description/android-mini-pos/icons/group-icon.svg';
import PersonalisedIcon from 'assets/pos/product-description/android-mini-pos/icons/personalised-icon.svg';
import StoreIcon from 'assets/pos/product-description/android-mini-pos/icons/store-icon.svg';
import TransactionsIcon from 'assets/pos/product-description/android-mini-pos/icons/transactions-icon.svg';
//Info Banners
import InfoBannerMobile from 'assets/pos/product-description/android-mini-pos/info-banner/info-banner-mobile.webp';
import InfoBanner from 'assets/pos/product-description/android-mini-pos/info-banner/info-banner.webp';
//Main Banners
import Main1 from 'assets/pos/product-description/android-mini-pos/main/main-1.webp';
import Main2 from 'assets/pos/product-description/android-mini-pos/main/main-2.webp';
import Main3 from 'assets/pos/product-description/android-mini-pos/main/main-3.webp';
import Main4 from 'assets/pos/product-description/android-mini-pos/main/main-4.webp';
//Thumbnails
import Thumbnail1 from 'assets/pos/product-description/android-mini-pos/thumbnails/thumbnail-1.webp';
import Thumbnail2 from 'assets/pos/product-description/android-mini-pos/thumbnails/thumbnail-2.webp';
import Thumbnail3 from 'assets/pos/product-description/android-mini-pos/thumbnails/thumbnail-3.webp';
import Thumbnail4 from 'assets/pos/product-description/android-mini-pos/thumbnails/thumbnail-4.webp';
import { ProductDescription } from 'merchant/views/POS/types';

const ANDROID_MINI_POS: ProductDescription = {
  gallery: [
    {
      main: Main1,
      mobile: Thumbnail1,
      thumbnail: Thumbnail1,
    },
    {
      main: Main2,
      mobile: Thumbnail2,
      thumbnail: Thumbnail2,
    },
    {
      main: Main3,
      mobile: Thumbnail3,
      thumbnail: Thumbnail3,
    },
    {
      main: Main4,
      mobile: Thumbnail4,
      thumbnail: Thumbnail4,
    },
  ],
  code: 'a50',
  name: 'android-mini-pos',
  productTitle: 'Android Smart Mini POS',
  description:
    'Android 8.1 or 10 Powered | Ultra slim | 4.5” HD Display | PCI PTS 5.x (Android 8.1) or 6.x (Android 10) SRED Certified | Inbuilt GPS',
  maxOrder: 0,
  cartImage: CartImage,
  pricing: [
    {
      name: 'Monthly Plan',
      type: 'monthly',
      subText: '*Subscription only starts when device gets delivered. GST charges applicable.',
      breakups: [
        {
          key: 'monthly',
          description: 'Monthly Subscription',
          value: 0,
          suffix: '/mo',
          isExtraFee: false,
          isChargeableAtCheckout: false,
          prevValue: null,
          nextValue: null,
        },
        {
          key: 'setup_fee',
          description: 'One Time Setup Fee',
          value: 0,
          suffix: 'setup fee',
          isExtraFee: true,
          isChargeableAtCheckout: true,
          prevValue: null,
          nextValue: null,
        },
      ],
    },
    {
      name: 'Lifetime Plan',
      type: 'lifetime',
      subText: '*No Setup fees required. GST charges applicable.',
      breakups: [
        {
          key: 'lifetime',
          description: 'Lifetime Plan',
          value: 0,
          suffix: '',
          isExtraFee: false,
          isChargeableAtCheckout: true,
          prevValue: null,
          nextValue: null,
        },
      ],
    },
  ],
  isPartnerPricing: false,
  featureGallery: [
    {
      image: Thumbnail3,
      title: 'Powerful and compact',
      description: 'Small in size and wireless for payments on the go',
      isImageFirst: false,
    },
    {
      image: Thumbnail2,
      title: 'Go paperless',
      description: 'Generate instant e-receipts for all transactionss',
      isImageFirst: true,
    },
    {
      image: Thumbnail4,
      title: 'Sound Alerts',
      description: 'Get instant audio confirmation for QR & card payments',
      isImageFirst: false,
    },
  ],
  infoBanner: {
    image: InfoBanner,
    mobileImage: InfoBannerMobile,
    features: [
      {
        icon: StoreIcon,
        text: 'Best suited for small stores',
      },
      {
        icon: GroupIcon,
        text: 'Ideal for busting long queues at store counters',
      },
      {
        icon: PersonalisedIcon,
        text: 'Complete view of previous transactions for any time period',
      },
      {
        icon: TransactionsIcon,
        text: 'Diverse support channels: SMS, missed calls, WhatsApp, toll-free numbers, and in-app complaints',
      },
    ],
  },
  technicalSpecifications: [
    {
      category: 'OS',
      value: 'PayDroid Powered by Android 10.0 (PCI 6) or 8.1 (PCI 5)',
    },
    {
      category: 'Processor',
      value: 'Cortex A53 + ARM',
    },
    {
      category: 'Memory',
      value:
        '8GB eMMC Flash + 1GB DDR RAM | Optional: 16GB eMMC Flash + 2GB DDR RAM Extended microSD Card Slot Up to 128GB',
    },
    {
      category: 'Card Readers',
      value: 'Chip & PIN | Contactless',
    },
    {
      category: 'Cameras',
      value: '2MP Rear-Facing, Optional: 2MP Front-Facing + 8MP Rear-Facing',
    },
    {
      category: 'Displays',
      value: '4.5" FW 480 x 854 Pixels Multi-Point Capacitive Touch Screen',
    },
    {
      category: 'Comms',
      value: '4G + Wi-Fi® 2.4GHz + Bluetooth®',
    },
    {
      category: 'Battery',
      value: '2500mAh | 3.8V',
    },
    {
      category: 'Printer',
      value: 'No',
    },
    {
      category: 'SIM / SAM',
      value: '1 Nano SIM | Optional: 2 Nano SIM',
    },
    {
      category: 'Positioning',
      value: 'GPS | GLONASS | BEIDOU',
    },
    {
      category: 'Keys / Buttons',
      value: '4 Keys: Power ON/OFF | Volume+ | Volume- | Quick Scan Button',
    },
    {
      category: 'Audio',
      value: '1 Buzzer | 1 Speaker | 2 Microphone | 1 Audio Jack | 1 Receiver',
    },
    {
      category: 'Ports',
      value: '1 Type-C OTG | 5 PIN POGO PIN',
    },
    {
      category: 'Adapter',
      value: 'Input: 100-240V AC, 50Hz/60Hz | Output: 5.0V DC, 2.0A',
    },
    {
      category: 'Physical',
      value: '138 x 69.5 x 14mm, 161g (including battery)',
    },
    {
      category: 'Environmental',
      value:
        '0°C ~ 45°C (32°F ~ 113°F) Operating Temperature, Non-Charging -20°C ~ 70°C (-4°F ~ 158°F) Storage Temperature 5% ~ 95% Relative Humidity, Non-Condensing',
    },
    {
      category: 'Accessories',
      value:
        'B50: Charging Base | 1 Power Port (Type-C) | 98 x 62 x 49mm B51: 5-Devices Charging Base | 98 x 210 x 49mm',
    },
    {
      category: 'Certifications',
      value:
        'PCI PTS 6.x (Android 10), PCI PTS 5.x (Android 8.1) | EMV L1 & L2 | EMV contactless L1 | Visa payWave | Mastercard contactless | UPI qUICS | American Expresspay | Discover D-PAS | JCB J/Speedy | Interac Flash | Mastercard TQM | CE | RoHs | FCC | IC | UL | WPC | BIS | Anatel | ABECS',
    },
  ],
  offer: null,
};

export default ANDROID_MINI_POS;
