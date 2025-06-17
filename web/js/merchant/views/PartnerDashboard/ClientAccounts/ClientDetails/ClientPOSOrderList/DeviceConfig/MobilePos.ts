import CartImage from 'assets/pos/product-description/mobile-pos/cart-img.webp';
import BellIcon from 'assets/pos/product-description/mobile-pos/icons/bell-icon.svg';
import DeliveryIcon from 'assets/pos/product-description/mobile-pos/icons/delivery-icon.svg';
import MethodsIcon from 'assets/pos/product-description/mobile-pos/icons/methods-icon.svg';
import SyncIcon from 'assets/pos/product-description/mobile-pos/icons/sync-icon.svg';
//Cart image
//Info Banners
import InfoBannerMobile from 'assets/pos/product-description/mobile-pos/info-banner/info-banner-mobile.webp';
import InfoBanner from 'assets/pos/product-description/mobile-pos/info-banner/info-banner.webp';
//Main Banners
import Main1 from 'assets/pos/product-description/mobile-pos/main/main-1.webp';
import Main2 from 'assets/pos/product-description/mobile-pos/main/main-2.webp';
import Main3 from 'assets/pos/product-description/mobile-pos/main/main-3.webp';
import Main4 from 'assets/pos/product-description/mobile-pos/main/main-4.webp';
//Thumbnails
import Thumbnail1 from 'assets/pos/product-description/mobile-pos/thumbnails/thumbnail-1.webp';
import Thumbnail2 from 'assets/pos/product-description/mobile-pos/thumbnails/thumbnail-2.webp';
import Thumbnail3 from 'assets/pos/product-description/mobile-pos/thumbnails/thumbnail-3.webp';
import Thumbnail4 from 'assets/pos/product-description/mobile-pos/thumbnails/thumbnail-4.webp';
import { ProductDescription } from '../types';

const MOBILE_POS: ProductDescription = {
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
  code: 'd180',
  name: 'mobile-pos',
  productTitle: 'Mobile POS (mPOS)',
  description:
    'Android and iOS compatible | PCI PTS 5.x Certified | USB Port for high-speed data transmission |  128 x 64 pixels LCD Display',
  maxOrder: null,
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
      title: 'Light and portable',
      description: 'For hassle-free payment collection on delivery',
      isImageFirst: false,
    },
    {
      image: Thumbnail4,
      title: 'Unbeatable battery life',
      description: 'Several days of battery life on a single charge',
      isImageFirst: true,
    },
    {
      image: Thumbnail2,
      title: 'No SIM required',
      description: 'Syncs with your smartphone',
      isImageFirst: false,
    },
  ],
  infoBanner: {
    image: InfoBanner,
    mobileImage: InfoBannerMobile,
    features: [
      {
        icon: DeliveryIcon,
        text: 'Best suited for delivery and collections',
      },
      {
        icon: SyncIcon,
        text: 'Sync with your smartphone via Bluetooth and USB ',
      },
      {
        icon: MethodsIcon,
        text: 'Accept all modes of payment - UPI, Credit Card and Debit Card',
      },
      {
        icon: BellIcon,
        text: 'Get sound notifications on your phone',
      },
    ],
  },
  technicalSpecifications: [
    {
      category: 'Processor',
      value: '32-bit ARM CPU',
    },
    {
      category: 'Memory',
      value: '128KB SRAM + 1MB Flash | External Storage: 4MB Flash',
    },
    {
      category: 'Card Readers',
      value: 'Magnetic Card Reader I Smart Card Reader I Contactless Card Reader',
    },

    {
      category: 'Displays',
      value: 'USB port for high-speed data transmission',
    },
    {
      category: 'Comms',
      value: 'Bluetooth® wireless technology',
    },
    {
      category: 'Battery',
      value: '3.7V / 250mAh Rechargeable Li-ion Battery',
    },
    {
      category: 'Ports',
      value: '1 x Micro USB 2.0',
    },
    {
      category: 'Physical',
      value: 'L x W x H (in): 4.57 x 2.34 x .51 Weight: 2.9 oz',
    },
    {
      category: 'Environmental',
      value:
        '0°C ~ 50°C (32°F ~ 122°F) Operating Temperature -10°C ~ 70°C (14°F ~ 158°F) Storage Temperature 10% ~ 93% Relative Humidity, Non-condensing',
    },
    {
      category: 'Certifications',
      value:
        'PCI PTS 5.x SRED, EMV® L1 & L2, Visa payWave, Mastercard PayPass, American Express expresspay, Discover D-PAS',
    },
  ],
  offer: null,
  shouldShowProductVarietyTable: true,
  rentalDiscountPeriod: 3,
};

export default MOBILE_POS;
