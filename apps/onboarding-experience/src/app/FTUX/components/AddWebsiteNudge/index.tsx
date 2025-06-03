import React from 'react';
import { SparklesIcon, ArrowUpRightIcon } from '@razorpay/blade/components';
import PitchProducts from '@FTUX/components/PitchProducts';
import addWebsiteCardImg from '@OnboardingExperienceAssets/NoCodeProducts/PaymentPagesThumbnail.svg';

const AddWebsiteNudge = () => {
  const websiteSuggestions = [
    {
      tagIcon: SparklesIcon,
      tagText: 'Website verification is required',
      title: 'Payment Gateway on Website/App',
      description: 'Accept payments on your website or app with a single integration',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Add website/app',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => {},
      image: addWebsiteCardImg as string,
    },
  ];

  return (
    <PitchProducts
      title="Add your website"
      subtitle="Instant payment collection"
      products={websiteSuggestions}
    />
  );
};

export default AddWebsiteNudge;
