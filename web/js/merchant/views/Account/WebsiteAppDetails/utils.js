export const formatStatus = (status) => {
  switch (status) {
    case null:
      return 'details required';
    case 'submitted':
    case 'under_review':
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

export function getLatestNeedsClarificationComment(clarificationReasons) {
  if (!clarificationReasons) return [];

  const { clarification_reasons, nc_count: count } = clarificationReasons;
  const websiteComments = clarification_reasons.business_website || [];
  const appStoreComments = clarification_reasons.appstore_url || [];
  const playStoreComments = clarification_reasons.playstore_url || [];

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

  const isActivated = activationData?.activated === 1;

  let areSectionUrlsReceived = true;
  if (websiteComplianceData?.status === null || Array.isArray(websiteComplianceData))
    areSectionUrlsReceived = false;

  return (
    websiteComplianceData.isWebsiteSectionsApplicable &&
    (!isActivated || activationData.activation_status !== 'needs_clarification') &&
    !areSectionUrlsReceived &&
    activationData?.isTransacted
  );
}

export function isNudgeSoftForWebsiteCompliance(activationData, websiteComplianceData) {
  if (!activationData || !websiteComplianceData) return false;

  const isActivated = activationData?.activated === 1;

  let areSectionUrlsReceived = true;
  if (websiteComplianceData?.status === null || Array.isArray(websiteComplianceData))
    areSectionUrlsReceived = false;

  return (
    websiteComplianceData.isWebsiteSectionsApplicable &&
    (!isActivated || activationData.activation_status !== 'needs_clarification') &&
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
