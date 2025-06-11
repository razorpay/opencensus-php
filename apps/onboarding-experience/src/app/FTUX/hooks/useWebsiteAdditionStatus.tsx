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
import { hasAcceptedAnyPaymentChannel } from '@OnboardingExperienceCommons/utils/merchant';

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
    if (appstoreUrl) {
      websitePlatforms.push({
        title: 'iOS app',
        badge: {
          color: 'positive',
          content: AddWebsiteBadgeContent.VERIFIED,
        },
        content: 'You can now access live keys to set up payments',
      });
    }

    if (playstoreUrl) {
      websitePlatforms.push({
        title: 'Android app',
        badge: {
          color: 'positive',
          content: AddWebsiteBadgeContent.VERIFIED,
        },
        content: 'You can now access live keys to set up payments',
      });
    }

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

    // If the business URL was provided and verified during onboarding, we don't need to show the add website CTA
    if (addedBusinessURL) {
      return websitePlatforms;
    }

    /* These below conditions are to handle App edge cases:
       Merchant only intended to add one of Android/ios in onboarding, and also added it
       then we don't need to show the add website CTA
    */
    // Check if merchant intends to have Android or iOS apps
    const hasAndroidAppIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
      PAYMENT_CHANNEL_OPTIONS.Android,
    ]);
    const hasIOSAppIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
      PAYMENT_CHANNEL_OPTIONS.IOS,
    ]);
    const hasWebsiteIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
      PAYMENT_CHANNEL_OPTIONS.Websites,
    ]);

    // Determine if there are any platforms that the merchant wants but hasn't added yet
    const hasMissingPlatforms =
      (hasAndroidAppIntent && !playstoreUrl) ||
      (hasIOSAppIntent && !appstoreUrl) ||
      (hasWebsiteIntent && !addedBusinessURL);

    // If there are no missing platforms, return the current list
    if (!hasMissingPlatforms) {
      return websitePlatforms;
    }
  }

  // Determine current workflow status by combining multiple data sources
  let status = getWebsiteWorkflowStatus({
    workflow: businessWebsiteWorkflow,
    websiteVerificationData: websiteUpdateData,
  });

  // If the url has been submitted but there is no status progress and no api key access
  if ((appstoreUrl || playstoreUrl || websiteUrl) && !status && !hasApiKeyAccess) {
    status = WebsiteVerificationStatusEnum.NeedsUpdate;
  } else if (!status) {
    // In case there is no status progress, we need to show Add CTA
    status = WebsiteVerificationStatusEnum.NoWebsite;
  }

  // Get platform content according to the payment channel intent
  const platformContent = getWebsiteAdditionContent(paymentChannels);

  let activePlatform: WebsitePlatformData = { title: platformContent.title };

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
          action: AddWebsiteBannerActions.RESOLVE_BVS_CLARIFICATION,
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
          action: AddWebsiteBannerActions.RESOLVE_CLARIFICATION,
        },
        content: `Our team needs a few more details to verify your ${activePlatform.title}. Please provide them to complete the process.`,
      };
      break;
    case WebsiteVerificationStatusEnum.BvsInProgress:
      // BVS Review in progress - no action needed from merchant
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'information',
          content: AddWebsiteBadgeContent.UNDER_REVIEW,
        },
        content: `Your ${activePlatform.title} is under review. Expect an update within 10 minutes. In the meantime, set up payments in Test Mode in the next step.`,
      };
      break;
    case WebsiteVerificationStatusEnum.WorkflowInReview:
      // Review in progress - no action needed from merchant
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'information',
          content: AddWebsiteBadgeContent.UNDER_REVIEW,
        },
        content: `Your ${
          activePlatform.title
        } is under review. Expect an update by ${getETAfromOffset()}. In the meantime, set up payments in Test Mode in the next step.`,
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
          action: AddWebsiteBannerActions.ADD_WEBSITE,
        },
        content: `Please try adding the ${activePlatform.title} again. If the issue persists, reach out to our support team for assistance.`,
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
          action: AddWebsiteBannerActions.ADD_WEBSITE,
        },
        content:
          "Your website doesn't seem live. You can update the link with a live website when it is ready.",
      };
      break;
    case WebsiteVerificationStatusEnum.Rejected:
      // Formally rejected with specific reason from review team
      activePlatform = {
        ...activePlatform,
        cta: {
          label: `Update ${activePlatform.title}`,
          action: AddWebsiteBannerActions.UPDATE_WEBSITE,
        },
        customLabel: (
          <Text size="medium" weight="semibold" color="interactive.text.negative.normal">
            Rejected: {businessWebsiteWorkflow?.rejectionReason || 'This link is not approved!'}
          </Text>
        ),
        content: `Please rectify this on your ${activePlatform.title} and add again, or add a new ${activePlatform.title}.`,
      };
      break;
    case WebsiteVerificationStatusEnum.NeedsUpdate:
      activePlatform = {
        ...activePlatform,
        badge: {
          color: 'negative',
          content: AddWebsiteBadgeContent.KLA_ACTIVATED,
        },
        cta: {
          label: 'Update details',
          action: AddWebsiteBannerActions.UPDATE_WEBSITE,
        },
        content: `${activePlatform.title}${
          addedBusinessURL ? ` (${addedBusinessURL})` : ''
        } provided during onboarding could not be verified. Please edit your ${
          activePlatform.title
        }.`,
      };
      break;
    case WebsiteVerificationStatusEnum.NoWebsite:
      activePlatform = {
        ...activePlatform,
        content: platformContent.initialContent,
        cta: {
          label: `Add ${activePlatform.title}`,
          action: AddWebsiteBannerActions.ADD_WEBSITE,
        },
      };
      break;
  }

  if (activePlatform?.content && !(hasApiKeyAccess && addedBusinessURL))
    websitePlatforms.push(activePlatform);

  return websitePlatforms;
};
