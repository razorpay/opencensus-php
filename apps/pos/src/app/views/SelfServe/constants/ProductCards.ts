import AndroidMiniPosImage from 'assets/pos/main-banner/minipos-new.webp';
import MobilePosImage from 'assets/pos/main-banner/mobile-pos.webp';
import MPosImage from 'assets/pos/main-banner/mpos-mobile.webp';
import QRStickerMobileImage from 'assets/pos/main-banner/qrSticker-mobile.webp';
import SoundboxKitImage from 'assets/pos/main-banner/soundbox-kit.webp';
import SoundboxKitMobileImage from 'assets/pos/main-banner/soundbox-mobile.webp';
import StandeeImage from 'assets/pos/main-banner/sticker-standee.webp';

import {
  PosProductCardProps,
  PricingType,
} from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/PosProductCard';
import {
  ANDROID_MINI_POS,
  MOBILE_POS,
  SOUNDBOX,
  STANDEEANDSTICKER,
} from 'apps/pos/src/app/views/SelfServe/constants';
import {
  getAvailablePricingPlans,
  getInitialPlan,
  getPricingByProduct,
  getProductFromProductDescriptions,
} from 'apps/pos/src/app/views/SelfServe/helpers';

import { ProductDescription } from '../types';

export const getAllPosProducts = (
  productDescriptions: ProductDescription[],
  isMobile: boolean,
): PosProductCardProps[] => {
  const getAmount = (productCode: string) => {
    const productDescription = getProductFromProductDescriptions({
      code: productCode,
      productDescriptions,
    });
    if (!productDescription) {
      return {
        monthly: 0,
        setupFee: 0,
        lifetime: 0,
        offer: null,
      };
    }
    const amount = getPricingByProduct({
      productDescription: productDescription as ProductDescription,
    });
    return amount;
  };

  const getPricingDescription = (productCode: string) => {
    const productDesc = getProductFromProductDescriptions({
      code: productCode,
      productDescriptions,
    });

    const { offer } = getAmount(productCode);
    const isValidOffer =
      productDesc?.offer &&
      offer &&
      offer?.prevMonthly !== null &&
      offer?.prevSetupFee !== null &&
      offer?.nextMonthly !== null;

    const { hasLifetimePlan, hasMonthlyPlan } = getAvailablePricingPlans(productDesc);

    if (!isValidOffer && hasMonthlyPlan) {
      return [
        {
          type: 'no-offer-pricing' as PricingType,
          amount: getAmount(productCode),
        },
      ];
    }

    if (hasLifetimePlan && hasMonthlyPlan) {
      return [
        {
          type: 'monthly-rental' as PricingType,
          amount: getAmount(productCode),
        },
        {
          type: 'setup-fee' as PricingType,
          amount: getAmount(productCode),
        },
      ];
    }
    if (hasLifetimePlan && !hasMonthlyPlan) {
      return [
        {
          type: 'lifetime-pricing' as PricingType,
          amount: getAmount(productCode),
        },
      ];
    }
    return [
      {
        type: 'monthly-rental' as PricingType,
        amount: getAmount(productCode),
      },
      {
        type: 'setup-fee' as PricingType,
        amount: getAmount(productCode),
      },
    ];
  };

  return [
    {
      productDescription: getProductFromProductDescriptions({
        code: STANDEEANDSTICKER.code,
        productDescriptions,
      }),
      title: STANDEEANDSTICKER.productTitle,
      description: 'Simple and Integrated Experience',
      imageSrc: isMobile ? QRStickerMobileImage : StandeeImage,
      tncText: '',
      plan: getInitialPlan({
        productDescription: getProductFromProductDescriptions({
          code: STANDEEANDSTICKER.code,
          productDescriptions,
        }),
      }),
      variant: 'left',
      pricingDescription: [
        {
          type: 'free-item',
          amount: getAmount(STANDEEANDSTICKER.code),
        },
      ],
      cta: {
        primary: {
          title: 'Add to Cart',
          onClick: () => {},
        },
      },
      footer: {
        title: 'Special Offer!',
        description: 'Grab a Standee and 2 QR Stickers for FREE!',
      },
    },
    {
      productDescription: getProductFromProductDescriptions({
        code: SOUNDBOX.code,
        productDescriptions,
      }),
      title: SOUNDBOX.productTitle,
      description: 'Light-weight and Economical',
      imageSrc: isMobile ? SoundboxKitMobileImage : SoundboxKitImage,
      tncText: '*Lifetime Pricing also available.',
      plan: getInitialPlan({
        productDescription: getProductFromProductDescriptions({
          code: SOUNDBOX.code,
          productDescriptions,
        }),
      }),
      variant: 'right',
      pricingDescription: getPricingDescription(SOUNDBOX.code),
      cta: {
        primary: {
          title: 'Add to cart',
          onClick: () => {},
        },
        secondary: {
          title: 'Learn More',
          onClick: () => {},
        },
      },
      footer: {
        title: 'Special Offer!',
        description: 'Get 1 Standee + 2 QR Stickers FREE on purchase of a Soundbox',
      },
    },
    {
      productDescription: getProductFromProductDescriptions({
        code: ANDROID_MINI_POS.code,
        productDescriptions,
      }),
      title: ANDROID_MINI_POS.productTitle,
      description: 'Feature packed and portable',
      imageSrc: AndroidMiniPosImage,
      tncText: '*Lifetime Pricing also available.',
      plan: getInitialPlan({
        productDescription: getProductFromProductDescriptions({
          code: ANDROID_MINI_POS.code,
          productDescriptions,
        }),
      }),
      variant: 'left',
      pricingDescription: getPricingDescription(ANDROID_MINI_POS.code),
      cta: {
        primary: {
          title: 'Add to Cart',
          onClick: () => {},
        },
        secondary: {
          title: 'Learn More',
          onClick: () => {},
        },
      },
    },
    {
      productDescription: getProductFromProductDescriptions({
        code: MOBILE_POS.code,
        productDescriptions,
      }),
      title: MOBILE_POS.productTitle,
      description: 'Pocket-sized and affordable',
      imageSrc: isMobile ? MPosImage : MobilePosImage,
      tncText: '*Lifetime Pricing also available.',
      plan: getInitialPlan({
        productDescription: getProductFromProductDescriptions({
          code: MOBILE_POS.code,
          productDescriptions,
        }),
      }),
      variant: 'right',
      pricingDescription: getPricingDescription(MOBILE_POS.code),
      cta: {
        primary: {
          title: 'Add to Cart',
          onClick: () => {},
        },
        secondary: {
          title: 'Learn More',
          onClick: () => {},
        },
      },
    },
  ];
};
