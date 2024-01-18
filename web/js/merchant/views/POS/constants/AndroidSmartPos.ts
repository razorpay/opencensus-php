import CartImage from 'assets/pos/product-description/android-smart-pos/cart-img.webp';
import CounterLoop from 'assets/pos/product-description/android-smart-pos/icons/counter-loop.svg';
import IntegrationsIcon from 'assets/pos/product-description/android-smart-pos/icons/integrations.svg';
import RadixIcon from 'assets/pos/product-description/android-smart-pos/icons/radix.svg';
import TransactionsIcon from 'assets/pos/product-description/android-smart-pos/icons/transactions.svg';
import InfoBannerMobile from 'assets/pos/product-description/android-smart-pos/info-banner/info-banner-mobile.webp';
import InfoBanner from 'assets/pos/product-description/android-smart-pos/info-banner/info-banner.webp';
import Main1 from 'assets/pos/product-description/android-smart-pos/main/main-1.webp';
import Main2 from 'assets/pos/product-description/android-smart-pos/main/main-2.webp';
import Main3 from 'assets/pos/product-description/android-smart-pos/main/main-3.webp';
import Main4 from 'assets/pos/product-description/android-smart-pos/main/main-4.webp';
import MainMobile1 from 'assets/pos/product-description/android-smart-pos/main/main-mobile-1.webp';
import MainMobile2 from 'assets/pos/product-description/android-smart-pos/main/main-mobile-2.webp';
import MainMobile3 from 'assets/pos/product-description/android-smart-pos/main/main-mobile-3.webp';
import MainMobile4 from 'assets/pos/product-description/android-smart-pos/main/main-mobile-4.webp';
import Thumbnail1 from 'assets/pos/product-description/android-smart-pos/thumbnails/thumbnail-1.webp';
import Thumbnail2 from 'assets/pos/product-description/android-smart-pos/thumbnails/thumbnail-2.webp';
import Thumbnail3 from 'assets/pos/product-description/android-smart-pos/thumbnails/thumbnail-3.webp';
import Thumbnail4 from 'assets/pos/product-description/android-smart-pos/thumbnails/thumbnail-4.webp';
import { ProductDescription } from 'merchant/views/POS/types';

const ANDROID_SMART_POS: ProductDescription = {
  gallery: [
    {
      main: Main1,
      mobile: MainMobile1,
      thumbnail: Thumbnail1,
    },
    {
      main: Main2,
      mobile: MainMobile2,
      thumbnail: Thumbnail2,
    },
    {
      main: Main3,
      mobile: MainMobile3,
      thumbnail: Thumbnail3,
    },
    {
      main: Main4,
      mobile: MainMobile4,
      thumbnail: Thumbnail4,
    },
  ],
  code: 'a910',
  name: 'android-smart-pos',
  productTitle: 'Android Smart POS',
  description:
    'Android 6.0 or 7.0 Powered | 5” HD display | PCI 6 SRED Certified | Quad-core Cortex A7 Processor | Fast Thermal Printer',
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
        },
        {
          key: 'setup_fee',
          description: 'One Time Setup Fee',
          value: 0,
          suffix: 'setup fee',
          isExtraFee: true,
          isChargeableAtCheckout: true,
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
        },
      ],
    },
  ],
  featureGallery: [
    {
      image: MainMobile2,
      title: 'Easy billing',
      description: 'Comes with both e-receipt and paper billing options',
      isImageFirst: false,
    },
    {
      image: MainMobile3,
      title: 'Sound Alerts ',
      description: 'Instant audio confirmation for QR & card payments',
      isImageFirst: true,
    },
    {
      image: MainMobile4,
      title: 'Multiple Payment Modes',
      description:
        'Credit and debit cards (tap, dip, or swipe), QR, and more with a large screen display',
      isImageFirst: false,
    },
  ],
  infoBanner: {
    image: InfoBanner,
    mobileImage: InfoBannerMobile,
    features: [
      {
        icon: CounterLoop,
        text: 'Best suited for countertop transactions',
      },
      {
        icon: RadixIcon,
        text: 'Highest RAM & ROM capabilities with long battery life',
      },
      {
        icon: TransactionsIcon,
        text: 'Complete view of previous transactions and settlements for any time period',
      },
      {
        icon: IntegrationsIcon,
        text: 'Seamlessly integrates with existing billing systems',
      },
    ],
  },
  technicalSpecifications: [
    {
      category: 'OS',
      value: 'Paydroid Powered by Android 7.0',
    },
    {
      category: 'Processor',
      value: 'Cortex A7 + ARM',
    },
    {
      category: 'Memory',
      value:
        '8GB eMMC Flash + 1GB DDR RAM | Optional: 16GB eMMC Flash + 2GB DDR RAM Extended Micro SD Card Slot Up To 128GB',
    },
    {
      category: 'Card Readers',
      value: 'Chip & PIN | NFC Contactless | Magnetic Stripe',
    },
    {
      category: 'Cameras',
      value: '2MP Rear-Facing Optional: 5MP Rear-Facing | 0.3MP Front-Facing',
    },
    {
      category: 'Displays',
      value: '5" IPS WXGA 720 x 1280 Pixels Multi-Point Capacitive HD Touch Screen',
    },
    {
      category: 'Comms',
      value: '4G + WiFi® (2.4GHz, optional 5GHz) + Bluetooth® 4.0',
    },
    {
      category: 'Battery',
      value: '2600mAh / 7.2V | Optional 3350mAh / 7.2V',
    },
    {
      category: 'Printer',
      value: '40 Lines/Sec | Paper roll outer diameter: 40mm',
    },
    {
      category: 'SIM / SAM',
      value: '1 x SIM + 2 x SAM | Optional: 2 x SIM + 1 x SAM',
    },
    {
      category: 'Positioning',
      value: 'GPS',
    },
    {
      category: 'Keys / Buttons',
      value: '3 Keys: Power ON/OFF | Volume+ | Volume-',
    },
    {
      category: 'Audio',
      value: '1 Buzzer | 1 Speaker | 1 microphone',
    },
    {
      category: 'Ports',
      value: '1 Type C USB OTG | 1 Audio Jack',
    },
    {
      category: 'Adapter',
      value: 'Input: 100 - 240V AC, 50Hz / 60Hz | Output: 5.0V DC, 2.0A',
    },
    {
      category: 'Physical',
      value: '3 Keys: Power ON/OFF | Volume+ | Volume-',
    },
    {
      category: 'Environmental',
      value:
        '-10°C ~ 50°C (14°F ~ 122°F) Operating Temperature -20°C ~ 70°C (-4°F ~ 158°F) Storage Temperature 5% ~ 96% Relative Humidity, Non-Condensing',
    },
    {
      category: 'Accessories',
      value:
        'B910-BC: Charging Base | 1 Power Port (Type C) | 129 * 83 * 29 mm B910-BM: Charging Base + LAN | 1 RS232 (RJ45) | 1 Ethernet (RJ45) | 1 Power Port (Type C) | 2 USB Type A Port (Host) | 189 * 92 * 44 mm B910-BE: Charging Base + Wireless | WiFi® 2.4G + Bluetooth® 4.0 | 1 RS232 (RJ45) | 1 Ethernet (RJ45) | 1 Power port (Type C) | 1 USB Type C (Device) | 1 USB Type A (Host) | 189 * 92 * 44 mm',
    },
    {
      category: 'Certifications',
      value:
        'PCI PTS 6.x SRED | EMV L1 & L2 | EMV Contactless L1 | Visa payWave | MasterCard Contactless | UPI qUICS | Amex ExpressPay | Discover D-PAS | JCB J/Speedy | MasterCard TQM | CE | RoHs | ABECS',
    },
  ],
};

export default ANDROID_SMART_POS;
