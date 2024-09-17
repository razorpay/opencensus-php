import QRSticker from 'assets/pos/product-description/qr-sticker/thumbnail-1.webp';
import CartImage from 'assets/pos/product-description/soundbox/cart-img.webp';
import AlarmIcon from 'assets/pos/product-description/soundbox/icons/alarm.svg';
import BrightIcon from 'assets/pos/product-description/soundbox/icons/brightness.svg';
import ConnectivityIcon from 'assets/pos/product-description/soundbox/icons/connectivity.svg';
import TransactionsIcon from 'assets/pos/product-description/soundbox/icons/transaction-history.svg';
//Info Banners
import InfoBannerMobile from 'assets/pos/product-description/soundbox/info-banner/info-banner-mobile.webp';
import InfoBanner from 'assets/pos/product-description/soundbox/info-banner/info-banner.webp';
//Main Banners
import Main2 from 'assets/pos/product-description/soundbox/main/main-2.webp';
import Main3 from 'assets/pos/product-description/soundbox/main/main-3.webp';
import Main4 from 'assets/pos/product-description/soundbox/main/main-4.webp';
import Main1 from 'assets/pos/product-description/soundbox/main/main-5.webp';
//Thumbnails
import Thumbnail2 from 'assets/pos/product-description/soundbox/thumbnails/thumbnail-2.webp';
import Thumbnail3 from 'assets/pos/product-description/soundbox/thumbnails/thumbnail-3.webp';
import Thumbnail4 from 'assets/pos/product-description/soundbox/thumbnails/thumbnail-4.webp';
import Thumbnail1 from 'assets/pos/product-description/soundbox/thumbnails/thumbnail-5.webp';
import Standee from 'assets/pos/product-description/standee/thumbnail-1.webp';

import { ProductDescription } from 'merchant/views/POS/types';

const SOUNDBOX: ProductDescription = {
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
  code: 'wd10',
  name: 'android-mini-pos',
  productTitle: 'Soundbox Kit',
  description:
    '1x Speaker, Volume up to 100 dB | 32-bit ARM CPU | 2000mAh Li-ion Battery. 1x Standee, 2x QR Stickers.',
  maxOrder: 9,
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
          value: 999,
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
      image: Thumbnail2,
      title: 'Voice alerts',
      description: 'Instant audio confirmation on successful UPI payments',
      isImageFirst: false,
    },
    {
      image: Thumbnail3,
      title: 'Always Connected',
      description: 'Connect seamlessly using a SIM card',
      isImageFirst: true,
    },
    {
      image: Thumbnail4,
      title: 'Long-lasting battery life',
      description: 'Powerful battery that charges via micro USB',
      isImageFirst: false,
    },
  ],
  infoBanner: {
    image: InfoBanner,
    mobileImage: InfoBannerMobile,
    features: [
      {
        icon: BrightIcon,
        text: 'Clear QR code display',
      },
      {
        icon: ConnectivityIcon,
        text: 'LED indicators to confirm connectivity',
      },
      {
        icon: AlarmIcon,
        text: 'Sound notifications on updates and charging',
      },
      {
        icon: TransactionsIcon,
        text: 'Transaction history available on the mPOS app',
      },
    ],
  },
  technicalSpecifications: [
    {
      category: 'Model',
      value: 'WD10 (With optional dynamic QR display)',
    },
    {
      category: 'Processor',
      value: '32-bit ARM based',
    },
    {
      category: 'Memory',
      value: 'RAM: 16MB ROM:16MB',
    },
    {
      category: 'Speaker',
      value: '403W, 1105dB >( 1M)',
    },
    {
      category: 'Charging',
      value: 'DC 5V/1A, USB Type-C connector',
    },
    {
      category: 'SIM',
      value: 'Single nano SIM slot',
    },
    {
      category: 'QR code size',
      value: 'Maximum 50mm',
    },
    {
      category: 'Ideal runtime',
      value: '200broadcastsadayfor 3days',
    },
    {
      category: 'Application',
      value: 'Supermarket, Convenience Store, Restaurant, Parking lot, Beauty Salon, Hotel',
    },
    {
      category: 'Language Support',
      value: 'Hindi, English (Other languages are customizable)',
    },
    {
      category: 'Operating Voltage',
      value: '3.7V - 4.2V',
    },
    {
      category: 'Standby current',
      value: '4G: 10mA; WIFI: 40mA',
    },
    {
      category: 'Data encryption mode',
      value: 'TLS',
    },
    {
      category: 'Communication Network',
      value: '2G,4G CAT1 / GPRS; WIFI (Optional)',
    },
    {
      category: 'Communication protocol',
      value: 'MQT',
    },
    {
      category: 'Frequency band',
      value: 'TDD-LTE: B34/B38/B39/B40/B41; GSM:900MHz/1800MHz',
    },
    {
      category: 'Environment',
      value: 'Operating temperature: -10°C ~ +60°C; Storage temperature: -20°C ~+70°C',
    },
    {
      category: 'Button',
      value: '1*Power Key 1*Function Key 2*Volume Up/Down Keys',
    },
    {
      category: 'Indicator Lights',
      value: '3color LED indicator light (blue, green and red)',
    },
    {
      category: 'Weight',
      value: '330g',
    },
    {
      category: 'Dimension',
      value:
        'Sound box size: 114mm*56mm*59mm | Panel size: Length xbreadth 114mm*155mm (Thickness:3.5mm)',
    },
    {
      category: 'Battery',
      value: '3.7V 2000mAh lithium manganate battery; Standby: ≥120H',
    },
  ],
  offer: null,
  shouldShowProductVarietyTable: false,
  rentalDiscountPeriod: 3,
  linkedItems: [
    {
      image: QRSticker,
      title: 'QR Sticker',
      offerLabel: 'Free with Combo Offer',
      quantity: 2,
    },
    {
      image: Standee,
      title: 'Standee',
      offerLabel: 'Free with Combo Offer',
      quantity: 1,
    },
  ],
};

export default SOUNDBOX;
