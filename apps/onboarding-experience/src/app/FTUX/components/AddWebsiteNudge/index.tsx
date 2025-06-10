import React, { lazy, Suspense, useState } from 'react';
import { SparklesIcon, ArrowUpRightIcon } from '@razorpay/blade/components';
import PitchProducts from '@FTUX/components/PitchProducts';
import addWebsiteCardImg from '@OnboardingExperienceAssets/NoCodeProducts/PaymentPagesThumbnail.svg';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { mapToWebsiteUpdateData } from '@FTUX/utils/homepage';
import { ModalStatus } from '@FTUX/types/common';
import { WebsiteSubmitModalSteps } from '@federated/dashboards/payments/types/websites';
import { scrollTo } from '@libs/shared-utils';

// Import the WebsiteV2Modal component
const WebsiteV2Modal = lazy(
  () =>
    import(
      /* webpackChunkName: "WebsiteV2Modal" */ '@federated/dashboards/payments/components/WebsiteModalsWrapper'
    ),
);

const AddWebsiteNudge = () => {
  const { onboardingData, refetchAllData, initiateTwoFaAuth } = useMerchantContext();
  const [websiteModalStatus, setWebsiteModalStatus] = useState<ModalStatus>(ModalStatus.INITIAL);

  const websiteUpdateData =
    onboardingData?.merchantOnboardingData?.websiteVerificationUpdateStatus?.verificationStatus;

  const handleAddWebsite = async () => {
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
      tagIcon: SparklesIcon,
      tagText: 'Website verification is required',
      title: 'Payment Gateway on Website/App',
      description: 'Accept payments on your website or app with a single integration',
      linkIcon: ArrowUpRightIcon,
      linkText: 'Add website/app',
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      handleClick: handleAddWebsite,
      image: addWebsiteCardImg as string,
      isCtaLoading: websiteModalStatus === ModalStatus.LOADING,
    },
  ];

  const handleRefetchData = () => {
    scrollTo({ endPos: 0 });
    refetchAllData();
  };

  return (
    <>
      <PitchProducts title="Add your website" products={websiteSuggestions} />
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
