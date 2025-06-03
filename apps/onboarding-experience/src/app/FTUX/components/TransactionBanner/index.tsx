import React from 'react';
import { ArrowRightIcon, Button } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import Banner, { BannerPropsType } from '@OnboardingExperienceCommons/components/Banner';
import TransactionBannerBg from '@OnboardingExperienceAssets/TransactionBannerBg.svg';
import TransactionBannerBgMobile from '@OnboardingExperienceAssets/TransactionBannerBgMobile.svg';
import TransactionBannerIcon from '@OnboardingExperienceAssets/TransactionBannerIcon.svg';

const TransactionBanner = () => {
  const isMobile = isMobileDevice();

  const TransactionBannerProps: BannerPropsType = {
    title: 'Great news, you’ve received new transactions!',
    description:
      'We’ve emailed your invoice. Your payment will be deposited into <Bank account number> on <date>.',
    subDescription:
      'Go ahead and view your transactions, or explore other payment modes before continuing.',
    CTA: () => (
      <Button
        onClick={() => {}} // Navigate to the transactions page
        size={isMobile ? 'small' : 'medium'}
        icon={ArrowRightIcon}
        iconPosition="right"
      >
        Proceed to transactions and settlements
      </Button>
    ),
    iconSrc: TransactionBannerIcon,
    bgImage: TransactionBannerBg,
    bgImageMobile: TransactionBannerBgMobile,
  };

  return <Banner {...TransactionBannerProps} />;
};

export default TransactionBanner;
