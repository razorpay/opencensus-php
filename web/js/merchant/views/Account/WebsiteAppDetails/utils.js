import { isExperimentEnabled } from 'common/splitz/utils';

export const formatStatus = (status) => {
  switch (status) {
    case null:
      return 'details required';
    case 'submitted':
    case 'under_review':
    case 'kyc_qualified_unactivated':
    case 'rejected':
      return 'under review';
    case 'needs_clarification':
      return 'action required';
    case 'approved':
      return 'verified';
    default:
      return 'details required';
  }
};

export const getStatusClass = (status) => {
  switch (status) {
    case 'details required':
      return 'details-required';
    case 'under review':
      return 'under-review';
    case 'action required':
      return 'action-required';
    case 'verified':
      return 'verified';
    case 'rejected':
      return 'rejected';
    default:
      return 'details required';
  }
};

const EXCLUDED_ACTIVATION_STATUS_LIST = [
  'activated',
  'needs_clarification',
  'activated_kyc_pending',
  'kyc_qualified_unactivated',
];

export function getLatestNeedsClarificationComment(clarificationReasons) {
  if (!clarificationReasons) return [];

  const { clarification_reasons, nc_count: count } = clarificationReasons;
  const websiteComments = clarification_reasons?.business_website || [];
  const appStoreComments = clarification_reasons?.appstore_url || [];
  const playStoreComments = clarification_reasons?.playstore_url || [];

  const sources = ['admin', 'system'];

  const business_website = websiteComments
    .filter((comment) => {
      if (comment.nc_count && comment.nc_count === count && sources.includes(comment.from))
        return true;
      else return false;
    })
    .map((item) => ({
      ...item,
      type: 'website',
    }));
  //
  const appstore = appStoreComments
    .filter((comment) => {
      if (comment.nc_count && comment.nc_count === count && sources.includes(comment.from))
        return true;
      else return false;
    })
    .map((item) => ({
      ...item,
      type: 'appstore',
    }));
  //
  const playstore = playStoreComments
    .filter((comment) => {
      if (comment.nc_count && comment.nc_count === count && sources.includes(comment.from))
        return true;
      else return false;
    })
    .map((item) => ({
      ...item,
      type: 'playstore',
    }));
  //
  const comments = [...business_website, ...appstore, ...playstore];
  return comments;
}

export function isNudgeHardForWebsiteCompliance(activationData, websiteComplianceData) {
  if (!activationData || !websiteComplianceData) return false;

  let areSectionUrlsReceived = true;
  if (Array.isArray(websiteComplianceData)) {
    areSectionUrlsReceived = false;
  } else if (!websiteComplianceData?.status) {
    // either status key isn't present || status === null
    areSectionUrlsReceived = false;
  }

  return (
    websiteComplianceData.isWebsiteSectionsApplicable &&
    !EXCLUDED_ACTIVATION_STATUS_LIST.includes(activationData.activation_status) &&
    !areSectionUrlsReceived &&
    activationData?.isTransacted
  );
}

export function isNudgeSoftForWebsiteCompliance(activationData, websiteComplianceData) {
  if (!activationData || !websiteComplianceData) return false;

  let areSectionUrlsReceived = true;
  if (Array.isArray(websiteComplianceData)) {
    areSectionUrlsReceived = false;
  } else if (!websiteComplianceData?.status) {
    // either status key isn't present || status === null
    areSectionUrlsReceived = false;
  }

  return (
    websiteComplianceData.isWebsiteSectionsApplicable &&
    !EXCLUDED_ACTIVATION_STATUS_LIST.includes(activationData.activation_status) &&
    !areSectionUrlsReceived
  );
}

export function shouldShowWebsiteComplianceModal(
  activationData,
  websiteComplianceData,
  visibilityData,
) {
  const shouldShowWebsiteCompliancePrompt = isNudgeSoftForWebsiteCompliance(
    activationData.data,
    websiteComplianceData.data,
  );
  const totalViewCount = Number(visibilityData.data.website_incomplete_soft_nudge_count);
  const previousViewTimestamp = Number(visibilityData.data.website_incomplete_soft_nudge_timestamp);
  const previousViewedDate = new Date(previousViewTimestamp * 1000).getUTCDate();
  const currentDate = new Date(Date.now()).getUTCDate();

  return (
    shouldShowWebsiteCompliancePrompt &&
    (currentDate !== previousViewedDate ||
      visibilityData.data.website_incomplete_soft_nudge_timestamp === null) &&
    (totalViewCount > 0 || visibilityData.data.website_incomplete_soft_nudge_count === null)
  );
}

export function isUrlFieldEmpty(activationData) {
  if (!activationData) return false;

  const businessWebsiteUrl = activationData.business_website;
  const appStoreUrl = activationData.appstore_url;
  const playStoreUrl = activationData.playstore_url;

  return !businessWebsiteUrl && !appStoreUrl && !playStoreUrl;
}

export const isPolicyWizardV2Enabled = ({ splitz, activationData, user }) => {
  const { abExperiments: { noCodePolicyWizard, policyWizardV2 } = {} } = splitz;

  const isNoCodePolicyWizardEnabled = isExperimentEnabled(noCodePolicyWizard) && user.isOrgRZP;
  const isPolicyExp = isExperimentEnabled(policyWizardV2) && user.isOrgRZP;

  const isWebsiteMerchant = !isUrlFieldEmpty(activationData);

  // const isNoCodeMerchantNotEnabled = !isWebsiteMerchant && !isNoCodePolicyWizardEnabled;
  const isWebsiteMerchantNotEnabled = isWebsiteMerchant && !isPolicyExp;

  // let isEnablePolicyWizardV2 = isPolicyExp;
  let isEnablePolicyWizardV2 = isNoCodePolicyWizardEnabled;

  // if no-code merhcant experiment is not enabled
  // and merchant is no-code merchant then it should return `false`
  // commented till BE is done for website policy
  // if (isNoCodeMerchantNotEnabled) {
  //   isEnablePolicyWizardV2 = false;
  // }

  // if website merhcant experiment is not enabled
  // and merchant is website merchant then it should return `false`
  if (isWebsiteMerchantNotEnabled) {
    isEnablePolicyWizardV2 = false;
  }

  return isEnablePolicyWizardV2;
};
