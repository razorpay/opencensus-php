import CartImage from 'assets/pos/product-description/qr-sticker/thumbnail-1.webp';
//Info Banners
import InfoBannerMobile from 'assets/pos/product-description/soundbox/info-banner/info-banner-mobile.webp';
import InfoBanner from 'assets/pos/product-description/soundbox/info-banner/info-banner.webp';

import { ProductDescription } from 'apps/pos/src/app/views/SelfServe/types';

const STANDEEANDSTICKER: ProductDescription = {
  gallery: [],
  code: 'rzp qr sticker and standee',
  name: 'sticker-and-standee',
  productTitle: 'QR Standee + Sticker Kit',
  description: 'Simple and Integrated Experience',
  maxOrder: 2,
  cartImage: CartImage,
  pricing: [],
  isPartnerPricing: false,
  featureGallery: [],
  infoBanner: {
    image: InfoBanner,
    mobileImage: InfoBannerMobile,
    features: [],
  },
  technicalSpecifications: [],
  offer: null,
  shouldShowProductVarietyTable: false,
  maxQuantityErrMsg: () => 'You have reached the maximum limit for the free kits',
  rentalDiscountPeriod: 3,
};

export default STANDEEANDSTICKER;
