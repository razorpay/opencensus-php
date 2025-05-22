import React, { useState } from 'react';
import { SparklesIcon, ArrowUpRightIcon, ClockIcon } from '@razorpay/blade/components';
import PitchProducts from '@FTUX/components/PitchProducts';
import ProductRecommender from '@FTUX/modals/ProductRecommender';
import RightProductForYouImg from 'apps/onboarding-experience/src/assets/RightProductForYouCard.svg';
import PaymentLinksImg from 'apps/onboarding-experience/src/assets/PaymentLinksCard.svg';
import PaymentPagesImg from 'apps/onboarding-experience/src/assets/PaymentPagesCard.svg';

const NocodeSection = () => {
  const [isProductRecommenderModalVisible, setProductRecommenderModalVisible] =
    useState<boolean>(false);

  const noCodeSuggestions = [
    {
      tagIcon: SparklesIcon,
      tagText: 'Personalised to you',
      title: 'Find the right product for you',
      description: "Tell us your needs, and we'll recommend the best Razorpay solution for you.",
      linkIcon: ArrowUpRightIcon,
      linkText: 'Find the right product',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => setProductRecommenderModalVisible(true),
      image: RightProductForYouImg,
    },
    {
      tagIcon: ClockIcon,
      tagText: '2 mins setup',
      title: 'Payment Pages',
      description:
        'Create a simple checkout page to accept payments online. No website or coding needed.',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Use now',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: PaymentPagesImg,
    },
    {
      tagIcon: ClockIcon,
      tagText: '2 mins setup',
      title: 'Payment Links',
      description:
        'Generate a link you can share with customers to get paid instantly, without any setup.',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Use now',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: PaymentLinksImg,
    },
  ];

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
