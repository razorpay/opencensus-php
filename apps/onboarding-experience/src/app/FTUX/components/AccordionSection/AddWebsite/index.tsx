import React, { lazy, Suspense, useState } from 'react';
import { Badge, Box, Button, Divider, Text } from '@razorpay/blade/components';
import { AddWebsiteBadgeContent, AddWebsiteBannerActions } from '@FTUX/types/addWebsites';
import { useWebsiteAdditionStatus } from '@FTUX/hooks/useWebsiteAdditionStatus';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { mapToWebsiteUpdateData } from '@FTUX/utils/homepage';
import { isMobileDevice } from '@libs/shared-utils';
import WebsiteStatusLoader from './WebsiteStatusLoader';
import { WebsiteSubmitModalSteps } from '@federated/dashboards/payments/types/websites';
import { ModalStatus } from '@FTUX/types/common';

// Import the WebsiteV2Modal component
const WebsiteV2Modal = lazy(
  () =>
    import(
      /* webpackChunkName: "WebsiteV2Modal" */ '@federated/dashboards/payments/components/WebsiteModalsWrapper'
    ),
);

/**
 * AddWebsite Component
 *
 * Displays a list of website platforms that can be added to the user's account
 * during the first-time user experience (FTUX) onboarding flow.
 * Each platform includes metadata like status badges and appropriate CTAs.
 */
const AddWebsite = ({ handleAddLater }: { handleAddLater: () => void }) => {
  const isMobile = isMobileDevice();

  // Get the list of website platforms with their statuses from the hook
  const { onboardingData, refetchAllData, isRefetchingAllData, initiateTwoFaAuth } =
    useMerchantContext();
  const websitePlatforms = useWebsiteAdditionStatus();
  const [activeModalStep, setActiveModalStep] = useState<WebsiteSubmitModalSteps | null>(null);
  const [websiteModalStatus, setWebsiteModalStatus] = useState<ModalStatus>(ModalStatus.INITIAL);

  const websiteUpdateData =
    onboardingData?.merchantOnboardingData?.websiteVerificationUpdateStatus?.verificationStatus;

  const handleCTAClick = async (action?: string): Promise<void> => {
    setWebsiteModalStatus(ModalStatus.LOADING);

    switch (action) {
      case AddWebsiteBannerActions.AddWebsite:
      case AddWebsiteBannerActions.UpdateWebsite:
        const twoFaSuccess = await initiateTwoFaAuth?.();
        if (!twoFaSuccess) {
          setActiveModalStep(null);
          setWebsiteModalStatus(ModalStatus.INITIAL);
          return;
        }
        setActiveModalStep(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);
        break;
      case AddWebsiteBannerActions.ResolveClarification:
        setActiveModalStep(WebsiteSubmitModalSteps.WEBSITE_NC_RAISED);
        break;
      case AddWebsiteBannerActions.ResolveBvsClarification:
        setActiveModalStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
        break;
      default:
        break;
    }
  };

  return (
    <Box display="flex" flexDirection="column" gap={{ base: 'spacing.5', m: 'spacing.7' }}>
      {websitePlatforms.map((platform, index) => (
        <React.Fragment key={platform.title}>
          {platform.badge?.content !== AddWebsiteBadgeContent.VERIFIED && isRefetchingAllData ? (
            <WebsiteStatusLoader />
          ) : (
            <Box display="flex" flexDirection="column" gap="spacing.5">
              <Box display="flex" flexDirection="row" gap="spacing.5" alignItems="center">
                <Text color="surface.text.gray.normal" size="medium" weight="medium">
                  {platform.title}
                </Text>
                {platform.badge ? (
                  <Badge size={isMobile ? 'small' : 'medium'} color={platform.badge.color}>
                    {platform.badge.content}
                  </Badge>
                ) : null}
              </Box>
              {platform.customLabel}
              <Text color="surface.text.gray.subtle" size={isMobile ? 'small' : 'medium'}>
                {platform.content}
              </Text>
              {platform.cta ? (
                <Box display="flex" flexDirection={{ base: 'column', m: 'row' }} gap="spacing.5">
                  <Button
                    size={isMobile ? 'small' : 'medium'}
                    variant={platform.cta.variant}
                    onClick={() => handleCTAClick(platform.cta?.action)}
                    isDisabled={websiteModalStatus === ModalStatus.LOADING}
                    isLoading={websiteModalStatus === ModalStatus.LOADING}
                    isFullWidth={isMobile}
                  >
                    {platform.cta.label}
                  </Button>
                  {/* For Add Website CTAs, give option to add later */}
                  {[
                    AddWebsiteBannerActions.AddWebsite,
                    AddWebsiteBannerActions.UpdateWebsite,
                  ].includes(platform.cta?.action) && (
                    <Button
                      size={isMobile ? 'small' : 'medium'}
                      variant="secondary"
                      onClick={handleAddLater}
                      isFullWidth={isMobile}
                    >
                      I'll add this later
                    </Button>
                  )}
                </Box>
              ) : null}
            </Box>
          )}

          {/* Add divider between items, except after the last item */}
          {index + 1 < websitePlatforms?.length ? <Divider /> : null}
        </React.Fragment>
      ))}

      {!!activeModalStep && (
        <Suspense fallback={<></>}>
          <WebsiteV2Modal
            onDismiss={() => {
              setActiveModalStep(null);
              setWebsiteModalStatus(ModalStatus.INITIAL);
            }}
            refreshWebsiteData={refetchAllData}
            activeStep={activeModalStep}
            websiteUpdateData={mapToWebsiteUpdateData(websiteUpdateData) as undefined}
          />
        </Suspense>
      )}
    </Box>
  );
};

export default AddWebsite;
