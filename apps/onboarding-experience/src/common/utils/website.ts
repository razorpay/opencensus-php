import {
  MerchantWebsiteCheckStatusEnum,
  SelfServeWorkflowStatus,
  WebsiteVerificationAutomationStatus,
  WebsiteVerificationStatusEnum,
  WebsiteVerificationUpdateStatus,
  MERCHANT_CUSTOMER_ACTIONS,
} from 'apps/onboarding-experience/src/common/types/onboarding';

// Regex patterns for app marketplace URLs to differentiate between mobile and web platforms
export const playStoreRegex = /^https:\/\/play\.google\.com\/store\/apps\/details\?id=.*/;

export const appStoreRegex = /^https:\/\/apps\.apple\.com\/.*/;

export enum AvailablePlatformTypesEnum {
  ANDROID = 'android',
  IOS = 'ios',
  WEBSITE = 'website',
}

/**
 * Determines the platform type from a business URL
 * Used for platform-specific verification processes and UI rendering
 */
export const getBusinessPlatformType = (url?: string): AvailablePlatformTypesEnum => {
  if (url && playStoreRegex.test(url)) {
    return AvailablePlatformTypesEnum.ANDROID;
  } else if (url && appStoreRegex.test(url)) {
    return AvailablePlatformTypesEnum.IOS;
  }
  return AvailablePlatformTypesEnum.WEBSITE;
};

const respondedWorkflowStatus = ['OPEN', 'APPROVED'];
const responseRequiredWorkflowStatus = ['OPEN', 'APPROVED'];
const rejectedWorkflowStatus = ['REJECTED'];
const reviewWorkflowStatus = ['OPEN', 'APPROVED'];

/**
 * Checks if a workflow requires clarification from the merchant
 * Critical for determining when to show clarification UI elements
 *
 * @param workflow Current workflow state
 * @param statuses List of valid statuses where clarification is applicable
 */
export const isWorkflowInClarification = (
  workflow: SelfServeWorkflowStatus | undefined,
  statuses: string[],
): boolean => {
  if (!workflow) return false;

  const { workflowStatus, needsClarificationMessage, isRequestUnderBvsValidation } = workflow;
  return (
    statuses.includes(workflowStatus as string) &&
    !!needsClarificationMessage &&
    !isRequestUnderBvsValidation
  );
};

/**
 * Determines if merchant can modify their website information
 */
export const isWorkflowChangeAllowed = (workflow: SelfServeWorkflowStatus | undefined): boolean => {
  if (workflow?.isWorkflowExits) return false;

  if (['OPEN', 'APPROVED'].includes(workflow?.workflowStatus as string)) return false;

  return true;
};

/**
 * Core function that determines the current verification status
 * Used to drive UI state transitions and expose next steps to merchants
 *
 * The priority order of status determination follows the verification lifecycle:
 * 1. Success states (completed/executed)
 * 2. BVS clarification needed (failed checks requiring merchant action)
 * 3. In-progress verification
 * 4. Review states
 * 5. Clarification needed
 * 6. Rejected state
 * 7. Fallback to update needed
 */
export const getWebsiteWorkflowStatus = ({
  workflow,
  websiteVerificationData,
}: {
  workflow?: SelfServeWorkflowStatus;
  websiteVerificationData?: WebsiteVerificationUpdateStatus;
}): WebsiteVerificationStatusEnum | null => {
  const {
    workflowStatus,
    needsClarificationMessage,
    customerActions,
    isRequestUnderBvsValidation,
    rejectedAt,
  } = workflow ?? {};

  const {
    currentStatus,
    currentStatusUpdatedAt,
    websiteVerificationStage,
    websiteVerificationPageStatus,
  } = websiteVerificationData ?? {};

  // Compare Rejection time with websiteVerificationData, to make sure it was rejected recently
  const isRejectedRecently =
    rejectedAt && currentStatusUpdatedAt
      ? new Date(rejectedAt) > new Date(currentStatusUpdatedAt)
      : false;

  // Review state occurs when the workflow is either under active review
  // or business verification service (BVS) validation
  const hasReviewStatus =
    (reviewWorkflowStatus.includes(workflowStatus as string) && !needsClarificationMessage) ||
    isRequestUnderBvsValidation;

  // Rejected state when workflow is terminated with rejection and not under BVS validation
  const hasRejectedStatus =
    rejectedWorkflowStatus.includes(workflowStatus as string) && !isRequestUnderBvsValidation;

  // Merchant has responded to clarification request but we're still reviewing
  const hasCustomerRespondedStatus =
    isWorkflowInClarification(workflow, respondedWorkflowStatus) &&
    customerActions?.includes(MERCHANT_CUSTOMER_ACTIONS.CUSTOMER_RESPONDED);

  // Merchant needs to respond to our clarification request
  const hasAwaitingCustomerResponseStatus =
    isWorkflowInClarification(workflow, responseRequiredWorkflowStatus) &&
    customerActions?.includes(MERCHANT_CUSTOMER_ACTIONS.AWAITING_CUSTOMER_RESPONSE);

  let status: WebsiteVerificationStatusEnum | null = null;

  // Check if all required pages have passed verification or are exempt
  const isAllPagesVerified =
    websiteVerificationPageStatus &&
    Object.values(websiteVerificationPageStatus).every(
      (page: any) =>
        page?.verified === MerchantWebsiteCheckStatusEnum.PASSED ||
        page?.verified === MerchantWebsiteCheckStatusEnum.NOT_APPLICABLE,
    );

  if (
    currentStatus &&
    [
      WebsiteVerificationAutomationStatus.COMPLETED,
      WebsiteVerificationAutomationStatus.WORKFLOW_COMPLETED,
      WebsiteVerificationAutomationStatus.WORKFLOW_EXECUTED,
    ].includes(currentStatus) &&
    isWorkflowChangeAllowed(workflow)
  ) {
    status = WebsiteVerificationStatusEnum.Success;
  }
  // BVS check failed but MCC and keyword checks have started - merchant must address issues
  else if (
    currentStatus === WebsiteVerificationAutomationStatus.IN_PROGRESS &&
    websiteVerificationStage?.bvsCheckStatus === MerchantWebsiteCheckStatusEnum.FAILED &&
    websiteVerificationStage?.mccCheckStatus !== MerchantWebsiteCheckStatusEnum.INITIATED &&
    websiteVerificationStage?.negativeKeywordCheckStatus !==
      MerchantWebsiteCheckStatusEnum.INITIATED &&
    !isAllPagesVerified
  ) {
    status = WebsiteVerificationStatusEnum.BvsNeedsClarification;
  }
  // Verification checks still running
  else if (
    currentStatus &&
    [WebsiteVerificationAutomationStatus.IN_PROGRESS].includes(currentStatus)
  ) {
    status = WebsiteVerificationStatusEnum.BvsInProgress;
  }
  // Either under review or merchant has responded to clarification and we're reviewing
  else if (hasReviewStatus || hasCustomerRespondedStatus) {
    status = WebsiteVerificationStatusEnum.WorkflowInReview;
  }
  // Waiting for merchant to respond to clarification request
  else if (hasAwaitingCustomerResponseStatus) {
    status = WebsiteVerificationStatusEnum.WorkflowNeedsClarification;
  } else if (hasRejectedStatus && isRejectedRecently) {
    // Final rejection state
    status = WebsiteVerificationStatusEnum.Rejected;
  } else if (
    currentStatus &&
    [
      WebsiteVerificationAutomationStatus.WEBSITE_UPDATE_FAILED,
      WebsiteVerificationAutomationStatus.WORKFLOW_CREATION_FAILED,
    ].includes(currentStatus)
  ) {
    status = WebsiteVerificationStatusEnum.WebsiteUpdateFailed;
  } else if (currentStatus === WebsiteVerificationAutomationStatus.WEBSITE_LIVENESS_FAILED) {
    status = WebsiteVerificationStatusEnum.WebsiteLivenessFailed;
  }

  return status;
};
