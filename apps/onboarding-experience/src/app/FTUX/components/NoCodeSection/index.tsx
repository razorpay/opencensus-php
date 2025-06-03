import React, { useMemo, useState } from 'react';
import { SparklesIcon, ArrowUpRightIcon, ClockIcon } from '@razorpay/blade/components';
import PitchProducts from '@FTUX/components/PitchProducts';
import ProductRecommender from '@FTUX/modals/ProductRecommender';
import { isMobileDevice } from '@libs/shared-utils';
import paymentLinksThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/PaymentLinksThumbnail.svg';
import paymentLinksMWeb from '@OnboardingExperienceAssets/NoCodeProducts/PaymentLinksMWeb.svg';
import paymentPagesMWeb from '@OnboardingExperienceAssets/NoCodeProducts/PaymentPagesMWeb.svg';
import paymentPagesThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/PaymentPagesThumbnail.svg';
import RightProductForYouMWeb from '@OnboardingExperienceAssets/NoCodeProducts/RightProductForYouMWeb.svg';
import RightProductForYouThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/RightProductForYouThumbnail.svg';
import { AVAILABLE_PRODUCTS_MAP, PRODUCT_TYPES } from '@FTUX/constants/products';

const NocodeSection = () => {
  const [isProductRecommenderModalVisible, setProductRecommenderModalVisible] =
    useState<boolean>(false);
  const isMobile = isMobileDevice();

  const noCodeSuggestions = useMemo(
    () => [
      {
        tagIcon: SparklesIcon,
        tagText: 'Personalised to you',
        title: 'Find the right product for you',
        description: "Tell us your needs, and we'll recommend the best Razorpay solution for you.",
        linkIcon: ArrowUpRightIcon,
        linkText: 'Find the right product',
        // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
        handleClick: () => setProductRecommenderModalVisible(true),
        image: isMobile ? RightProductForYouMWeb : RightProductForYouThumbnail,
      },
      {
        ...AVAILABLE_PRODUCTS_MAP[PRODUCT_TYPES.PAYMENT_PAGES],
        image: isMobile ? paymentPagesMWeb : paymentPagesThumbnail,
      },
      {
        ...AVAILABLE_PRODUCTS_MAP[PRODUCT_TYPES.PAYMENT_LINKS],
        image: isMobile ? paymentLinksMWeb : paymentLinksThumbnail,
      },
    ],
    [isMobile],
  );

  return (
    <>
      <PitchProducts
        title="Ready-to-use products"
        subtitle="Instant payment collection"
        products={noCodeSuggestions}
      />
      {isProductRecommenderModalVisible && (
        <ProductRecommender onDismiss={() => setProductRecommenderModalVisible(false)} />
      )}
    </>
  );
};

export default NocodeSection;
