import React from 'react';
import { isMobileDevice } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { Button, ArrowRightIcon } from '@razorpay/blade/components';
import Banner, { BannerPropsType } from '@OnboardingExperienceCommons/components/Banner';
import useMerchant from '@OnboardingExperienceCommons/hooks/useMerchant';
import { MerchantActivationDataType } from '@OnboardingExperienceCommons/types/merchant';
import {
  checkIfNCEnabled,
  checkIfUnderReview,
  getCountryOnboardingUrl,
  getNCUrlOnEasyOrPhantom,
} from '@OnboardingExperienceCommons/utils/merchant';
import { CountryCodeType } from '@razorpay/i18nify-js';
import PreactivationBannerBg from '@OnboardingExperienceAssets/PreactivationBannerBg.svg';
import PreactivationBannerBgMobile from '@OnboardingExperienceAssets/PreactivationBannerBgMobile.svg';
import PreactivationBannerIcon from '@OnboardingExperienceAssets/PreactivationBannerIcon.svg';
import NcBannerBg from '@OnboardingExperienceAssets/NcBannerBg.svg';
import NcBannerBgMobile from '@OnboardingExperienceAssets/NCBannerBgMobile.svg';
import NcBannerIcon from '@OnboardingExperienceAssets/NcBannerIcon.svg';

const index = () => {
  const isMobile = isMobileDevice();
  const { user: activeUser } = useStore((state) => state.session);
  const { data: { merchantById: merchantData = {} as MerchantActivationDataType } = {} } =
    useMerchant();

  /**
   * navigateToEasyOnboarding: Opens the main Easy onboarding flow in a new tab
   * navigateToEasyNC: Opens the Needs Clarification (NC) flow in a new tab for merchants who need to provide additional KYC details
   */
  const navigateToEasyOnboarding = () =>
    window.open(getCountryOnboardingUrl(activeUser.country_code as CountryCodeType), '_blank');

  const navigateToEasyNC = () =>
    window.open(getNCUrlOnEasyOrPhantom(activeUser.signup_campaign || ''), '_blank');

  // Props for the onboarding pending banner
  const preactivationBannerProps: BannerPropsType = {
    title: 'Complete your onboarding to accept payments',
    description:
      'KYC verification is needed to collect live payments across channels. Meanwhile, you can explore our test experience.',
    CTA: () => (
      <Button
        onClick={navigateToEasyOnboarding}
        size={isMobile ? 'small' : 'medium'}
        icon={ArrowRightIcon}
        iconPosition="right"
      >
        Complete Onboarding
      </Button>
    ),
    iconSrc: PreactivationBannerIcon,
    bgImage: PreactivationBannerBg,
    bgImageMobile: PreactivationBannerBgMobile,
  };

  // Props for the under review banner
  const underReviewBannerProps: BannerPropsType = {
    title: 'Your application is under review',
    description:
      'Post application approval, you can collect live payments across channels. Meanwhile, you can explore our test experience.',
    iconSrc: PreactivationBannerIcon,
    bgImage: PreactivationBannerBg,
    bgImageMobile: PreactivationBannerBgMobile,
  };

  // Props for the NC banner
  const ncBannerProps: BannerPropsType = {
    title: 'We need to clarify some details',
    description:
      'Resolve all open points to start collecting payments. The sooner you do, the faster you can go live.',
    CTA: () => (
      <Button
        onClick={navigateToEasyNC}
        size={isMobile ? 'small' : 'medium'}
        icon={ArrowRightIcon}
        iconPosition="right"
      >
        Resolve NC
      </Button>
    ),
    iconSrc: NcBannerIcon,
    bgImage: NcBannerBg,
    bgImageMobile: NcBannerBgMobile,
  };

  /**
   * Check if the merchant is in NC (Needs Clarification) status and set the appropriate banner props
   */
  let bannerProps: BannerPropsType = preactivationBannerProps;
  if (checkIfNCEnabled(merchantData.activation)) {
    bannerProps = ncBannerProps;
  } else if (checkIfUnderReview(merchantData.activation)) {
    bannerProps = underReviewBannerProps;
  }

  return <Banner {...bannerProps} />;
};

export default index;
