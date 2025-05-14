import React from 'react';
import { SparklesIcon, ArrowUpRightIcon, ClockIcon } from '@razorpay/blade/components';
import PitchProducts from '@FTUX/components/PitchProducts';
import ProductCardImg from 'apps/onboarding-experience/src/assets/CardBanner.svg';

const NocodeSection = () => {
  const noCodeSuggestions = [
    {
      tagIcon: SparklesIcon,
      tagText: 'Payment',
      title: 'Instant payment collection',
      description: 'Accept payments on your website with a single integration',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Learn more',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: ProductCardImg as string,
    },
    {
      tagIcon: ClockIcon,
      tagText: 'Set up in 2 mins',
      title: 'Payment Pages',
      description:
        'Get your own checkout page to sell/ accept payment online, even without a website.',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Use now',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: ProductCardImg as string,
    },
    {
      tagIcon: ClockIcon,
      tagText: 'Set up in 2 mins',
      title: 'Payment Links',
      description: 'Share a link on WhatsApp, SMS, or email and get paid immediately.',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Use now',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: ProductCardImg as string,
    },
  ];

  return (
    <PitchProducts
      title="Ready-to-use products"
      subtitle="Instant payment collection"
      products={noCodeSuggestions}
    />
  );
};

export default NocodeSection;
