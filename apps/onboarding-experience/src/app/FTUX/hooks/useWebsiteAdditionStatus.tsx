import React from 'react';
import { Text } from '@razorpay/blade/components';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import {
  AddWebsiteBadgeContent,
  AddWebsiteBannerActions,
  WebsitePlatformData,
} from '@FTUX/types/addWebsites';
import {
  AvailablePlatformTypesEnum,
  getBusinessPlatformType,
  getWebsiteWorkflowStatus,
} from '@OnboardingExperienceCommons/utils/website';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';
import { WebsiteVerificationStatusEnum } from '@OnboardingExperienceCommons/types/onboarding';
import { getETAfromOffset } from '@OnboardingExperienceCommons/utils/dateAndTime';
import { getWebsiteAdditionContent } from '@FTUX/utils/homepage';

/**
 * Hook that determines the verification status for all platform types (website, iOS, Android)
 * Handles both verified platforms and those in various stages of the verification workflow
 * @returns Array of platform data objects with appropriate status badges, CTAs, and messaging
 */
export const useWebsiteAdditionStatus = (): Array<WebsitePlatformData> => {
  const { merchantData, onboardingData } = useMerchantContext();
  let websitePlatforms: WebsitePlatformData[] = [];

  const websiteUpdateData =
    onboardingData?.merchantOnboardingData?.websiteVerificationUpdateStatus?.verificationStatus;
  const businessWebsiteWorkflow =
    onboardingData?.merchantOnboardingData?.selfServeWorkflowStatus?.selfServeWorkflow;

  const hasApiKeyAccess = merchantData?.merchantById?.hasApiKeyAccess;

  // Extract PaymentAcceptanceChannels data
  const paymentChannels = merchantData?.merchantById?.business?.paymentAcceptanceChannels;

  // Check for missing merchant data
  if (!paymentChannels) {
    return websitePlatforms;
  }

  // Extract primary business URL and determine its platform type (website, iOS, Android)
  const addedBusinessURL =
    paymentChannels[PAYMENT_CHANNEL_OPTIONS.Websites]?.urls?.[0]?.value || '';
  const businessURLType = getBusinessPlatformType(addedBusinessURL);

  // Determine platform URLs, handling cases where the primary URL might belong to any platform type
  const appstoreUrl =
    businessURLType === AvailablePlatformTypesEnum.IOS
      ? addedBusinessURL
      : paymentChannels[PAYMENT_CHANNEL_OPTIONS.IOS]?.urls?.[0]?.value;

  const playstoreUrl =
    businessURLType === AvailablePlatformTypesEnum.ANDROID
      ? addedBusinessURL
      : paymentChannels[PAYMENT_CHANNEL_OPTIONS.Android]?.urls?.[0]?.value;

  // Only set websiteUrl if the primary URL is actually a website
  const websiteUrl = businessURLType === AvailablePlatformTypesEnum.WEBSITE ? addedBusinessURL : '';

  if (hasApiKeyAccess) {
    // Handle verified platforms if user has api key access - these are URLs that already exist in merchant data
    if (websiteUrl) {
      websitePlatforms.push({
        title: `Website link`,
        badge: {
          color: 'positive',
          content: AddWebsiteBadgeContent.VERIFIED,
        },
        content: `Your website ${websiteUrl} is verified and ready to accept payments.`,
      });
    }

    if (appstoreUrl) {
      websitePlatforms.push({
        title: 'iOS app',
        badge: {
          color: 'positive',
          content: AddWebsiteBadgeContent.VERIFIED,
        },
        content: 'Your app is verified and ready to accept payments.',
      });
    }

    if (playstoreUrl) {
      websitePlatforms.push({
        title: 'Android app',
        badge: {
          color: 'positive',
          content: AddWebsiteBadgeContent.VERIFIED,
        },
        content: 'Your app is verified and ready to accept payments.',
      });
    }

    // If the business URL was provided and verified during onboarding, we don't need to show the add website CTA
    if (addedBusinessURL) {
      return websitePlatforms;
    }
  }

  // Handle in-progress verification workflows for any platform
  const mainPageUrl = websiteUpdateData?.mainPageUrl;
  const platformType = getBusinessPlatformType(mainPageUrl || '');

  // Determine current workflow status by combining multiple data sources
  let status = getWebsiteWorkflowStatus({
    workflow: businessWebsiteWorkflow,
    websiteVerificationData: websiteUpdateData,
  });

  // If the url has been submitted but there is no status progress and no api key access
  if (addedBusinessURL && !status && !hasApiKeyAccess) {
    status = WebsiteVerificationStatusEnum.NeedsUpdate;
  } else if (!status) {
    // In case there is no status progress, we need to show Add CTA
    status = WebsiteVerificationStatusEnum.NoWebsite;
  }

  // Initialize with platform-specific title before adding status-specific properties
  let activePlatform: WebsitePlatformData = {
    title:
      platformType === AvailablePlatformTypesEnum.WEBSITE
        ? 'Website link'
        : platformType === AvailablePlatformTypesEnum.IOS
        ? 'iOS app'
        : 'Android app',
  };

  // Handle each verification status with appropriate messaging and action buttons
  switch (status) {
    case WebsiteVerificationStatusEnum.BvsNeedsClarification:
      // Business Verification Service requires additional policy documentation
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.MISSING_BVS_PAGES,
        },
        cta: {
          label: 'Resolve now',
          action: AddWebsiteBannerActions.ResolveBvsClarification,
        },
        content: `Some policy details are missing on your website. Please update them or create policy pages using Razorpay.`,
      };
      break;
    case WebsiteVerificationStatusEnum.WorkflowNeedsClarification:
      // NC resolution needed with custom messaging from review team
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.NEEDS_CLARIFICATIONS,
        },
        cta: {
          label: 'Resolve now',
          action: AddWebsiteBannerActions.ResolveClarification,
        },
        content: `Our team needs a few more details to verify your ${
          activePlatform.title
        } (${mainPageUrl}): ${
          businessWebsiteWorkflow?.needsClarificationMessage || 'This link is not accepted!'
        }. Please provide them to complete the process.`,
      };
      break;
    case WebsiteVerificationStatusEnum.BvsInProgress:
    case WebsiteVerificationStatusEnum.WorkflowInReview:
      // Review in progress - no action needed from merchant
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'information',
          content: AddWebsiteBadgeContent.UNDER_REVIEW,
        },
        content: `You should receive an update about your website by ${getETAfromOffset()}. In the meantime, you can test the flow using Test mode. `,
      };
      break;
    case WebsiteVerificationStatusEnum.WebsiteUpdateFailed:
      // Rejected with specific reason due to system error
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.ERROR_OCCURED,
        },
        cta: {
          label: `Add ${activePlatform.title}`,
          action: AddWebsiteBannerActions.AddWebsite,
        },
        content:
          'Please try adding the website again. If the issue persists, reach out to our support team for assistance.',
      };
      break;
    case WebsiteVerificationStatusEnum.WebsiteLivenessFailed:
      // Rejected because the website is not live
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.LIVENESS_FAILED,
        },
        cta: {
          label: `Update ${activePlatform.title}`,
          action: AddWebsiteBannerActions.AddWebsite,
        },
        content:
          "Your website doesn't seem live. You can update the link with a live website or mark it is as live when it is ready.",
      };
      break;
    case WebsiteVerificationStatusEnum.Rejected:
      // Formally rejected with specific reason from review team
      activePlatform = {
        ...activePlatform,
        cta: {
          label: `Update ${activePlatform.title}`,
          action: AddWebsiteBannerActions.UpdateWebsite,
        },
        customLabel: (
          <Text size="medium" weight="semibold" color="interactive.text.negative.normal">
            Rejected: {businessWebsiteWorkflow?.rejectionReason || 'This link is not approved!'}
          </Text>
        ),
        content: `Please rectify this on your website and add again, or add a new website.`,
      };
      break;
    case WebsiteVerificationStatusEnum.NeedsUpdate:
      activePlatform = {
        title: `Website / App`,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.KLA_ACTIVATED,
        },
        cta: {
          label: 'Update details',
          action: AddWebsiteBannerActions.UpdateWebsite,
        },
        content: `Website (${addedBusinessURL}) provided during onboarding could not be verified. Please edit your website details.`,
      };
      break;
    case WebsiteVerificationStatusEnum.NoWebsite:
      const newWebsiteAdditionContent = getWebsiteAdditionContent(paymentChannels);
      activePlatform = {
        title: newWebsiteAdditionContent.title,
        customLabel: newWebsiteAdditionContent.label ? (
          <Text color="surface.text.gray.subtle" size="medium">
            {newWebsiteAdditionContent.label}
          </Text>
        ) : undefined,
        content: newWebsiteAdditionContent.content,
        cta: {
          label: `Add ${newWebsiteAdditionContent.title}`,
          action: AddWebsiteBannerActions.AddWebsite,
        },
      };
      break;
  }

  if (activePlatform?.content && !(hasApiKeyAccess && addedBusinessURL))
    websitePlatforms.push(activePlatform);

  return websitePlatforms;
};
