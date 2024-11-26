import { User } from 'common/typings';
import { autoPrefixUrls } from 'common/utils/rzp-utils';
import { isEmail, isPhone, isValidWebsite } from 'common/utils/validators';
import { merchantFetch } from 'merchant/utils/ajax';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';

import { getBusinessPlatformType } from './components/utils';
import {
  BusinessWebsiteCardBadgeStatus,
  BusinessWebsiteWorkflow,
  Platform,
  RequireCredsValues,
  ValidationState,
  WebsiteUpdateApiData,
  WebsiteUpdateApiPayload,
  WebsiteUpdateAutomationStatus,
  WebsiteVerificationStatus,
  BusinessWebsiteCardData,
  GetCtaConditionArgs,
  GetCtaConditionData,
  MissingPagesFormFieldType,
  PolicyPagesSelection,
  PolicyPageCreationFormFieldType,
  WebsitePolicyPagesDetailsKeys,
  WebsitePolicyPages,
  MerchantWebsiteDetails,
} from './types';

export const WEBSITE_UPDATE_API_BASE_URL =
  'care_service/merchant/twirp/rzp.care.dashboard.accountAndSetting.v1.AccountAndSettingService';

export const isWorkflowChangeAllowed = (workflow) => {
  return (
    !workflow?.loading &&
    (workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(workflow?.workflow_status))
  );
};

export const hasBreachedAdditionalWebsiteLimit = (user: User) => {
  return user.additional_websites ? user.additional_websites.length >= 5 : false;
};

export const getCTACondition = ({
  isWebsiteDetailsFetching,
  isWebsiteDetailsFetchError,
  websiteUpdateData,
  businessWebsiteWorkflow,
  additionalWebsiteWorkflow,
  user,
}: GetCtaConditionArgs): GetCtaConditionData => {
  let ctaDisabledReason = 'Your updation request is in progress';
  const ctaText = !Boolean(user.business_website)
    ? 'Add website/app details'
    : 'Add additional website/app details';

  const isBvsFlowInProgress =
    websiteUpdateData?.current_status &&
    [
      WebsiteUpdateAutomationStatus.IN_PROGRESS,
      WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS,
    ].includes(websiteUpdateData?.current_status);

  if (isWebsiteDetailsFetching || isWebsiteDetailsFetchError || isBvsFlowInProgress) {
    return {
      isMainWebsiteEditActionAllowed: false,
      isAdditionalWebsiteActionAllowed: false,
      isAddActionAllowed: false,
      isAddFirstWebsiteAllowed: false,
      ctaText,
      ctaDisabledReason: isBvsFlowInProgress
        ? ctaDisabledReason
        : 'Please wait while we fetch the data',
    };
  }

  const isLimitReached = hasBreachedAdditionalWebsiteLimit(user);

  const isMainWebsiteWorkflowChangeAllowed = isWorkflowChangeAllowed(businessWebsiteWorkflow);
  const isAdditionalWebsiteWorfklowChangeAllowed =
    isWorkflowChangeAllowed(additionalWebsiteWorkflow);

  // condition for main website update allowed
  // 1. OCR automation should not be enabled
  // 2. No other workflow for main website shouldn't be in progress
  // 3. User should be owner
  const isMainWebsiteActionAllowedOld =
    !businessWebsiteWorkflow?.ocr_automated_check_enable &&
    isMainWebsiteWorkflowChangeAllowed &&
    user.isOwner;

  // condition for additional website update allowed
  // 1. User should have a main website
  // 2. Additional domain whitelist self serve should be on
  // 3. No other workflow for additional website shouldn't be in progress
  const isAdditionalWebsitedActionAllowedOld =
    Boolean(user.business_website) &&
    isAdditionalWebsiteWorfklowChangeAllowed &&
    user.isAdminOrOwner &&
    !isLimitReached;

  // combined conditions for main website update allowed
  // 1. Main website update allowed
  // 2. No other workflow for additional website shouldn't be in progress
  const isMainWebsiteEditActionAllowed =
    isMainWebsiteActionAllowedOld && isAdditionalWebsiteWorfklowChangeAllowed;

  // combined conditions for additional website update allowed
  // 1. Additional website update allowed
  // 2. No other workflow for main website shouldn't be in progress
  const isAdditionalWebsiteActionAllowed =
    isAdditionalWebsitedActionAllowedOld && isMainWebsiteWorkflowChangeAllowed;

  // Enable button if any of the above two conditions are true
  const isAddActionAllowed = isMainWebsiteEditActionAllowed || isAdditionalWebsiteActionAllowed;

  // if flow is first time main website add or adding the additional website
  const isAddFirstWebsiteAllowed = !user.business_website && user.isOwner;

  if (Boolean(user.business_website) && !isAdditionalWebsitedActionAllowedOld && isLimitReached) {
    ctaDisabledReason = 'You have reached the limit of 5 additional websites';
  }

  if (!user.isAdminOrOwner) {
    ctaDisabledReason = `Only ${
      user.business_website ? 'owner or admin' : 'owner'
    } can update the website details`;
  }

  return {
    isMainWebsiteEditActionAllowed,
    isAdditionalWebsiteActionAllowed,
    isAddActionAllowed,
    isAddFirstWebsiteAllowed,
    ctaText,
    ctaDisabledReason,
  };
};

export const mainPageFormDefaultValue = {
  platform: {
    value: '',
    valid: ValidationState.NONE,
  },
  url: {
    value: '',
    valid: ValidationState.NONE,
  },
  requireCreds: {
    value: '',
    valid: ValidationState.NONE,
  },
  credsUsername: {
    value: '',
    valid: ValidationState.NONE,
  },
  credsPassword: {
    value: '',
    valid: ValidationState.NONE,
  },
};

export const validators = {
  platform: (value): boolean => [Platform.WEBSITE, Platform.APP].includes(value),
  url: (value): boolean => isValidWebsite({ url: value }),
  requireCreds: (value): boolean => [RequireCredsValues.YES, RequireCredsValues.NO].includes(value),
  credsUsername: (value, formState): boolean =>
    formState.requireCreds.value === RequireCredsValues.YES ? !!value : true,
  credsPassword: (value, formState): boolean =>
    formState.requireCreds.value === RequireCredsValues.YES ? !!value : true,
};

export const mainPageFormValidator = (formState): boolean => {
  for (const field in formState) {
    if (formState.hasOwnProperty(field)) {
      if (!validators[field](formState[field].value, formState)) {
        return false;
      }
    }
  }
  return true;
};

export const policyPageFormValidator = (formState): { isValid: boolean; formState: any } => {
  const newFormState = { ...formState };
  let hasError = false;
  for (const field in newFormState) {
    if (newFormState.hasOwnProperty(field)) {
      const { value, radioValue } = newFormState[field] as MissingPagesFormFieldType;
      if (radioValue === PolicyPagesSelection.YES && value && validators.url(value)) {
        newFormState[field].valid = ValidationState.NONE;
      } else if (radioValue === PolicyPagesSelection.NO || radioValue === PolicyPagesSelection.NA) {
        newFormState[field].valid = ValidationState.NONE;
      } else {
        hasError = true;
        newFormState[field].valid = ValidationState.ERROR;
      }
    }
  }
  return { isValid: !hasError, formState: newFormState };
};

export const policyPageFormCreationValidator = (
  formState: PolicyPageCreationFormFieldType,
  questionaireMapping: Record<WebsitePolicyPagesDetailsKeys, boolean>,
): { isValid: boolean; formState: PolicyPageCreationFormFieldType } => {
  const newFormState = { ...formState };
  let hasError = false;
  for (const field in newFormState) {
    if (newFormState.hasOwnProperty(field) && questionaireMapping[field]) {
      const radioButtonsKeys = [
        WebsitePolicyPagesDetailsKeys.SHIPPING_PERIOD,
        WebsitePolicyPagesDetailsKeys.REFUND_REQUEST_PERIOD,
        WebsitePolicyPagesDetailsKeys.REFUND_PROCESS_PERIOD,
      ];
      if (radioButtonsKeys.includes(field as WebsitePolicyPagesDetailsKeys)) {
        if (!newFormState[field].value) {
          hasError = true;
          newFormState[field].valid = ValidationState.ERROR;
        }
      } else if (field === WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL) {
        if (!newFormState[field].value || !isEmail(newFormState[field].value)) {
          hasError = true;
          newFormState[field].valid = ValidationState.ERROR;
        }
      } else if (field === WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER) {
        if (!newFormState[field].value || !isPhone(newFormState[field].value)) {
          hasError = true;
          newFormState[field].valid = ValidationState.ERROR;
        }
      }
    }
  }
  return { isValid: !hasError, formState: newFormState };
};

export const isMainPageSubmitPayloadValid = (formState, userBusinessWebsite): [boolean, string] => {
  if (!mainPageFormValidator(formState)) {
    return [false, 'Please enter valid inputs.'];
  }

  if (autoPrefixUrls(formState.url.value, true) === userBusinessWebsite) {
    return [
      false,
      'Provided URL is same as the existing business website Url. Please provide a different URL.',
    ];
  }

  return [true, ''];
};

export function handleAppSubmitForActivated(formState): Promise<any> {
  const payload = {
    business_app_url: autoPrefixUrls(formState.url.value, true),
    ...(formState.requireCreds.value === RequireCredsValues.YES
      ? {
          business_app_username: formState.credsUsername.value,
          business_app_password: formState.credsPassword.value,
        }
      : {}),
  };

  return new Promise((resolve, reject) => {
    merchantFetch({
      url: `merchant/save_business_website/app`,
      method: 'POST',
      mode: 'live',
      data: payload,
      headers: {
        'Content-Type': 'application/json',
      },
    })
      .then((response) => {
        if (response.success) {
          resolve(response);
        } else {
          reject(response);
        }
      })
      .catch((err) => {
        reject(err.errors && err.errors[0] ? err.errors[0] : 'Failed to add app!');
      });
  });
}

export function handleAppAndWebsiteSubmitForNonActivated(formState): Promise<any> {
  return new Promise((resolve, reject) => {
    merchantFetch({
      url: 'merchant/activation/update_website_details',
      method: 'put',
      mode: 'live',
      data: { business_website: autoPrefixUrls(formState.url.value, true) },
    })
      .then((response) => {
        if (response.success) {
          resolve(response);
        } else {
          reject(response);
        }
      })
      .catch((err) => {
        reject(err.errors && err.errors[0] ? err.errors[0] : 'Failed to add website!');
      });
  });
}

export const getWebsiteMainPageSubmitPayload = ({ formState, mode }): WebsiteUpdateApiPayload => ({
  mode,
  main_page: {
    url: autoPrefixUrls(formState.url.value, true),
    ...(formState.requireCreds.value === 'yes'
      ? {
          main_page_credential: {
            username: formState.credsUsername.value,
            password: formState.credsPassword.value,
          },
        }
      : {}),
  },
});

export const getWebsitePolicyPagesSubmitPayload = ({
  formState,
  mode,
}): WebsiteUpdateApiPayload => {
  const policy_pages = Object.keys(formState).reduce(
    (acc, key) => {
      if (formState[key].radioValue === PolicyPagesSelection.YES) {
        acc[key] = {
          url: autoPrefixUrls(formState[key].value, true),
        };
      }
      return acc;
    },
    {
      is_shipping_page_required:
        formState[WebsitePolicyPages.SHIPPING].radioValue !== PolicyPagesSelection.NA,
    },
  );

  return {
    mode,
    policy_pages,
  };
};

export const snapPoints = [0.75, 0.8, 1.0];

export function getUnderReviewETA({ date = new Date(), offset = 60 * 60 * 1000 }) {
  const currentDate = new Date(date);
  const futureDate = new Date(currentDate.getTime() + offset);

  const options: Intl.DateTimeFormatOptions = {
    month: 'short',
    day: 'numeric',
  };

  // output: Nov 8
  const formattedDate = futureDate.toLocaleString('en-US', options);
  return formattedDate;
}

const respondedWorkflowStatus = ['open', 'approved'];
const responseRequiredWorkflowStatus = ['open', 'approved'];
const rejectedWorkflowStatus = ['rejected'];
const reviewWorkflowStatus = ['open', 'approved'];

interface WebsiteWorkflowStatusArgs {
  websiteUpdateData: WebsiteUpdateApiData;
  businessWebsiteWorkflow: BusinessWebsiteWorkflow;
}

export enum Status {
  Success = 'success',
  BvsNeedsClarification = 'bvs_needs_clarification',
  BvsInProgress = 'bvs_in_progress',
  WorkflowInReview = 'workflow_in_review',
  WorkflowNeedsClarification = 'workflow_needs_clarification',
  Rejected = 'rejected',
}

type NullableStatus = Status | null;
type NullableAnalyticsStatus = WebsiteUpdateAutomationStatus | Status | null;

export function getWebsiteWorkflowStatus({
  businessWebsiteWorkflow,
  websiteUpdateData,
}: WebsiteWorkflowStatusArgs): {
  status: NullableStatus;
  analyticsStatus: NullableAnalyticsStatus;
} {
  const {
    workflow_status,
    needs_clarification,
    tags,
    request_under_validation,
    ocr_automated_check_enable,
  } = businessWebsiteWorkflow ?? {};
  const { current_status, website_verification_stage, website_verification_page_status } =
    websiteUpdateData ?? {};

  const hasReviewStatus =
    (reviewWorkflowStatus.includes(workflow_status) && !needs_clarification) ||
    request_under_validation;

  const hasRejectedStatus =
    rejectedWorkflowStatus.includes(workflow_status) && !request_under_validation;

  const hasCustomerRespondedStatus =
    isWorkflowInClarification(businessWebsiteWorkflow, respondedWorkflowStatus) &&
    tags?.includes('customer-responded');

  const hasAwaitingCustomerResponseStatus =
    isWorkflowInClarification(businessWebsiteWorkflow, responseRequiredWorkflowStatus) &&
    tags?.includes('awaiting-customer-response');

  // status
  let status: NullableStatus = null;
  let analyticsStatus: NullableAnalyticsStatus = null;
  const isAllPagesVerified =
    website_verification_page_status &&
    Object.values(website_verification_page_status).every(
      (page) =>
        page?.verified === WebsiteVerificationStatus.PASSED ||
        page?.verified === WebsiteVerificationStatus.NOT_APPLICABLE,
    );

  if (
    current_status &&
    [
      WebsiteUpdateAutomationStatus.COMPLETED,
      WebsiteUpdateAutomationStatus.WORKFLOW_COMPLETED,
      WebsiteUpdateAutomationStatus.WORKFLOW_EXECUTED,
    ].includes(current_status) &&
    isWorkflowChangeAllowed(businessWebsiteWorkflow)
  ) {
    analyticsStatus = current_status;
    status = Status.Success;
  } else if (
    current_status === WebsiteUpdateAutomationStatus.IN_PROGRESS &&
    website_verification_stage?.bvs_check_status === WebsiteVerificationStatus.FAILED &&
    website_verification_stage?.mcc_check_status !== WebsiteVerificationStatus.INITIATED &&
    website_verification_stage?.negative_keyword_check_status !==
      WebsiteVerificationStatus.INITIATED &&
    !isAllPagesVerified
  ) {
    status = Status.BvsNeedsClarification;
  } else if (
    current_status &&
    [WebsiteUpdateAutomationStatus.IN_PROGRESS].includes(current_status)
  ) {
    status = Status.BvsInProgress;
  } else if (ocr_automated_check_enable || hasReviewStatus || hasCustomerRespondedStatus) {
    status = Status.WorkflowInReview;
  } else if (hasAwaitingCustomerResponseStatus) {
    status = Status.WorkflowNeedsClarification;
  } else if (hasRejectedStatus) {
    status = Status.Rejected;
  }

  return {
    status,
    analyticsStatus: analyticsStatus ?? status,
  };
}

export function getInProgressWebsiteStatusBadge({
  businessWebsiteWorkflow,
  websiteUpdateData,
}: {
  businessWebsiteWorkflow: Record<string, any>;
  websiteUpdateData: WebsiteUpdateApiData;
}) {
  const hasAwaitingCustomerResponseStatus =
    isWorkflowInClarification(businessWebsiteWorkflow, responseRequiredWorkflowStatus) &&
    businessWebsiteWorkflow?.tags?.includes('awaiting-customer-response');

  const { current_status, website_verification_stage, website_verification_page_status } =
    websiteUpdateData ?? {};
  const isAllPagesVerified =
    website_verification_page_status &&
    Object.values(website_verification_page_status).every(
      (page) =>
        page?.verified === WebsiteVerificationStatus.PASSED ||
        page?.verified === WebsiteVerificationStatus.NOT_APPLICABLE,
    );
  const hasPolicyPagesFixRequiredStatus =
    current_status === WebsiteUpdateAutomationStatus.IN_PROGRESS &&
    website_verification_stage?.bvs_check_status === WebsiteVerificationStatus.FAILED &&
    website_verification_stage?.mcc_check_status !== WebsiteVerificationStatus.INITIATED &&
    website_verification_stage?.negative_keyword_check_status !==
      WebsiteVerificationStatus.INITIATED &&
    !isAllPagesVerified;

  if (hasAwaitingCustomerResponseStatus || hasPolicyPagesFixRequiredStatus) {
    return BusinessWebsiteCardBadgeStatus.ACTION_REQUIRED;
  }
  return BusinessWebsiteCardBadgeStatus.UNDER_REVIEW;
}

export const getWebsiteCount = (user: User) => {
  let count = 0;
  if (user.business_website) {
    count++;
  }
  if (user.additional_websites) {
    count += user.additional_websites.length;
  }
  if (user.appstore_url) {
    count++;
  }
  if (user.playstore_url) {
    count++;
  }
  return count;
};

type NonNullableStatus = NonNullable<Status>;
export const alertText: Record<NonNullableStatus, string> = {
  [Status.Success]: 'Your website has been successfully verified',
  [Status.BvsNeedsClarification]: 'We found a few policy details missing on your website',
  [Status.BvsInProgress]: 'Your website verification request is under review',
  [Status.WorkflowInReview]: 'Your website verification request is under review',
  [Status.WorkflowNeedsClarification]: 'Our team needs a few more details to verify your website',
  [Status.Rejected]: 'Our team has rejected your website upon careful verification',
};

export const alertCTAText: Partial<Record<NonNullableStatus, string>> = {
  [Status.Success]: 'Download API Keys',
  [Status.BvsNeedsClarification]: 'Update now',
  [Status.WorkflowNeedsClarification]: 'Resolve now',
};

export const getBusinessWebsitesToShow = ({
  user,
  websiteUpdateData,
  businessWebsiteWorkflow,
}): Array<BusinessWebsiteCardData> => {
  const websites = [] as BusinessWebsiteCardData[];

  if (user.business_website) {
    websites.push({
      url: user.business_website,
      status: BusinessWebsiteCardBadgeStatus.ACTIVE,
      platform: getBusinessPlatformType(user.business_website),
      isPrimary: true,
    });
  }

  if (
    websiteUpdateData?.current_status &&
    [
      WebsiteUpdateAutomationStatus.IN_PROGRESS,
      WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS,
    ].includes(websiteUpdateData.current_status) &&
    websiteUpdateData.main_page_url
  ) {
    websites.push({
      url: websiteUpdateData.main_page_url,
      status: getInProgressWebsiteStatusBadge({
        businessWebsiteWorkflow,
        websiteUpdateData,
      }),
      platform: getBusinessPlatformType(websiteUpdateData.main_page_url),
      isPrimary: false,
    });
  }
  if (user.appstore_url) {
    websites.push({
      url: user.appstore_url,
      status: BusinessWebsiteCardBadgeStatus.ACTIVE,
      platform: 'App',
      isPrimary: false,
    });
  }

  if (user.playstore_url) {
    websites.push({
      url: user.playstore_url,
      status: BusinessWebsiteCardBadgeStatus.ACTIVE,
      platform: 'App',
      isPrimary: false,
    });
  }

  if (user.additional_websites?.length > 0) {
    user.additional_websites.forEach((website) => {
      websites.push({
        url: website,
        status: BusinessWebsiteCardBadgeStatus.ACTIVE,
        platform: getBusinessPlatformType(website),
        isPrimary: false,
      });
    });
  }

  return websites;
};

export const getMerchantWebsiteDetailsPayload = (policyPagesToBeMade) => {
  const result: MerchantWebsiteDetails = {} as MerchantWebsiteDetails;
  policyPagesToBeMade.forEach((page: WebsitePolicyPages) => {
    result[page] = { section_status: 3 };
  });
  return result;
};

export const shouldShowNotApplicableOption = (pageKey: WebsitePolicyPages): boolean =>
  [WebsitePolicyPages.SHIPPING].includes(pageKey);

export function extractTextFromHtmlElement(htmlContent, className = '') {
  const parser = new DOMParser();
  const doc = parser.parseFromString(htmlContent, 'text/html');

  if (!className) {
    return doc.body.textContent || doc.body.innerText || '';
  }

  const elements = doc.querySelectorAll(`.${className}`);
  const arrayOfElements = Array.from(elements);
  if (!arrayOfElements.length) {
    return '';
  }
  const extractedText = arrayOfElements.map((element) => element.textContent?.trim()).join('\n');

  return extractedText;
}
