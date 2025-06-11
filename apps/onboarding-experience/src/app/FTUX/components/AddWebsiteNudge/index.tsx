import React, { lazy, Suspense, useState } from 'react';
import { BankAccountVerificationIcon, ArrowUpRightIcon } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import PitchProducts from '@FTUX/components/PitchProducts';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { mapToWebsiteUpdateData } from '@FTUX/utils/homepage';
import { ModalStatus } from '@FTUX/types/common';
import { WebsiteSubmitModalSteps } from '@federated/dashboards/payments/types/websites';
import addWebsiteCardMWeb from '@OnboardingExperienceAssets/NoCodeProducts/AddWebsiteCardMWeb.svg';
import addWebsiteCardThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/AddWebsiteCardThumbnail.svg';

// Import the WebsiteV2Modal component
const WebsiteV2Modal = lazy(
  () =>
    import(
      /* webpackChunkName: "WebsiteV2Modal" */ '@federated/dashboards/payments/components/WebsiteModalsWrapper'
    ),
);

export enum PlatformType {
  WEBSITE = 'website',
  APP = 'app',
}

const AddWebsiteNudge = () => {
  const isMobile = isMobileDevice();
  const { onboardingData, refetchAllData, initiateTwoFaAuth } = useMerchantContext();
  const [websiteModalStatus, setWebsiteModalStatus] = useState<ModalStatus>(ModalStatus.INITIAL);
  const [activePlatformType, setActivePlatformType] = useState<PlatformType>(PlatformType.WEBSITE);

  const websiteUpdateData =
    onboardingData?.merchantOnboardingData?.websiteVerificationUpdateStatus?.verificationStatus;

  const handleVerification = async (type: PlatformType) => {
    setActivePlatformType(type);
    setWebsiteModalStatus(ModalStatus.LOADING);
    const twoFaSuccess = await initiateTwoFaAuth?.();
    if (!twoFaSuccess) {
      setWebsiteModalStatus(ModalStatus.INITIAL);
      return;
    }
    setWebsiteModalStatus(ModalStatus.OPEN);
  };

  const websiteSuggestions = [
    {
      tagIcon: BankAccountVerificationIcon,
      tagText: 'Verification required',
      title: 'Accept payments on Website',
      description:
        'Integrating Razorpay Payments is simple and fast. All you need is a live website to get started.',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Verify Now',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: () => handleVerification(PlatformType.WEBSITE),
      image: isMobile ? addWebsiteCardMWeb : addWebsiteCardThumbnail,
      isCtaLoading:
        websiteModalStatus === ModalStatus.LOADING && activePlatformType === PlatformType.WEBSITE,
    },
    // This is disabled for now as we are not supporting app verification yet
    // TODO: Enable this once we have app verification in place
    // {
    //   tagIcon: BankAccountVerificationIcon,
    //   tagText: 'Verification required',
    //   title: 'Accept payments on Apps',
    //   description:
    //     'Integrating Razorpay Payments is simple and fast. Choose between iOS, Android or both.',
    //   linkIcon: ArrowUpRightIcon,
    //   linkText: 'Verify Now',
    //   // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
    //   handleClick: () => handleVerification(PlatformType.APP),
    //   image: isMobile ? appCardMWeb : appCardThumbnail,
    //   isCtaLoading:
    //     websiteModalStatus === ModalStatus.LOADING && activePlatformType === PlatformType.APP,
    // },
  ];

  const handleRefetchData = () => {
    const element = document.getElementById('ftux-welcome-header');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
    refetchAllData();
  };

  return (
    <>
      <PitchProducts title="Setup payment gateway" products={websiteSuggestions} />
      {websiteModalStatus === ModalStatus.OPEN && (
        <Suspense fallback={<></>}>
          <WebsiteV2Modal
            onDismiss={() => {
              setWebsiteModalStatus(ModalStatus.INITIAL);
            }}
            activeStep={WebsiteSubmitModalSteps.ADD_MAIN_PAGE}
            websiteUpdateData={mapToWebsiteUpdateData(websiteUpdateData) as undefined}
            refreshWebsiteData={handleRefetchData}
          />
        </Suspense>
      )}
    </>
  );
};

export default AddWebsiteNudge;
