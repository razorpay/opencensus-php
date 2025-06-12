import {
  MerchantActivationDataType,
  MerchantActivationMilestoneEnum,
} from '@OnboardingExperienceCommons/types/merchant';

interface FtuxAnalyticsPropertiesResponse {
  intentForWebsite: boolean;
  submittedWebsite: boolean;
  websiteUrl?: string;
  liveApiGenerated: boolean;
  hasAppUrl: boolean;
  L1Submitted: boolean;
  L2Submitted: boolean;
  isTransacted: boolean;
  hasWebsite: boolean;
  MerchantActivationStatus?: string;
  ftuxType: string;
}

export const getFtuxAnalyticsProperties = (
  merchant: MerchantActivationDataType | undefined,
): FtuxAnalyticsPropertiesResponse => {
  const activationFormMilestone = merchant?.activation?.milestone;

  const l1Submitted = !!activationFormMilestone;

  const l2Submitted = activationFormMilestone === MerchantActivationMilestoneEnum?.L2_COMPLETED;

  const isTransacted = merchant?.activation?.isTransacted ?? false;

  const { websites, android, ios } = merchant?.business?.paymentAcceptanceChannels ?? {};
  const hasWebsite = [websites].some((channel) => channel?.accept && channel?.urls?.[0]?.value);
  const merchantActivationStatus = merchant?.activation?.status;

  const intentForWebsite = !!websites?.accept;
  const submittedWebsite = !!websites?.urls?.[0]?.value;
  const websiteUrl = websites?.urls?.[0]?.value;
  const liveApiGenerated = !!merchant?.hasApiKeyAccess;
  const hasAppUrl = !!android?.urls?.[0]?.value || !!ios?.urls?.[0]?.value;

  return {
    L1Submitted: l1Submitted,
    L2Submitted: l2Submitted,
    isTransacted,
    hasWebsite,
    MerchantActivationStatus: merchantActivationStatus,
    ftuxType: 'V2',
    intentForWebsite,
    submittedWebsite,
    websiteUrl,
    liveApiGenerated,
    hasAppUrl,
  };
};

export const setClarityTag = (key: string, value: string) => {
  try {
    (window as any)?.clarity?.('set', key, value);
  } catch (e) {}
};
