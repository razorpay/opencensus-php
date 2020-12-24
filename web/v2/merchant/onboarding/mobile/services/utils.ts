import {
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
  UNREGISTERED_TYPES,
  BUSINESS_PROOF_TYPE_DOCS,
  ORG_BusinessTypes,
  PROPRIETORSHIP,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
  ADDITIONAL_DOCS_REQUIRED_REG_BIZ,
} from '../Constants/OnboardingConstants';

export const getLabel = (field, data) => {
  const businessType = data.business_overview.business_type.value;
  let label = '';
  switch (field) {
    case 'promoter_pan':
      label = isUnregisteredBusiness(businessType)
        ? "Business Owner's PAN"
        : 'Authorised Signatory PAN';
      break;
    case 'promoter_pan_name':
      label = isUnregisteredBusiness(businessType)
        ? "Business Owner's Name"
        : 'Authorised Signatory Name';
      break;
    case 'company_cin':
      if (CIN_BusinessTypes.includes(Number(businessType))) {
        label = 'Company Identification Number (CIN)';
      } else if (LLPIN_BusinessTypes.includes(Number(businessType))) {
        label = 'LLP Identification Number (LLPIN)';
      } else label = '';
      break;
    default:
      label = '';
  }
  return label;
};

export const getHelpText = (field, data) => {
  const businessType = data.business_overview.business_type.value;
  let helpText = '';
  switch (field) {
    case 'promoter_pan':
      helpText = isUnregisteredBusiness(businessType) ? '' : 'PAN of one of the directors';
      break;
    default:
      helpText = '';
  }
  return helpText;
};

export const getMerchantFlow = (business_type: string, activation_flow: string): string => {
  if (business_type === '11') {
    return 'whitelist';
  }
  return activation_flow;
};

export const isL1Submitted = (onboarding_milestone: string | null): boolean => {
  if (!onboarding_milestone) {
    return false;
  }
  if (onboarding_milestone === 'activation_flow') {
    return false;
  }
  if (onboarding_milestone === 'L1') {
    return true;
  }
  return false;
};

export const isNone = (value) => {
  return value === null || value === undefined;
};

export function isBlank(value) {
  if (value !== null && typeof value === 'object') {
    return !Object.keys(value).length;
  }
  if (typeof value === 'string') {
    value = value.trim();
    return !value;
  }
  return isNone(value);
}

export function isPresent(obj) {
  return !isBlank(obj);
}

export function isUnregisteredBusiness(businessType): boolean {
  return !!UNREGISTERED_TYPES[Number(businessType)];
}

export function isRegAutoKYCEnabled() {
  return true;
}

export function hasUploadedBusinessProofTypeDoc(documents): boolean {
  return Object.keys(BUSINESS_PROOF_TYPE_DOCS).some((key) => isPresent(documents[key].value));
}

export function hasUploadedBusinessProofUrl(documents): boolean {
  return !!(
    documents &&
    documents.business_proof_url.value &&
    documents.business_proof_url.value.length
  );
}

export function showForOrgs(businessType): boolean {
  return businessType && ORG_BusinessTypes.indexOf(Number(businessType)) !== -1;
}

export function isBusinessProofTypeDocFieldVisible(data) {
  if (data.business_overview.business_type.value === PROPRIETORSHIP) {
    if (
      !(hasUploadedBusinessProofUrl(data.documents) && data.submitted) ||
      hasUploadedBusinessProofTypeDoc(data.documents)
    ) {
      return true;
    }
  }
  return false;
}
export function getBizCatSubCatPair(context) {
  const selectedBizCategory = context.business_overview.business_category.value;
  const selectedBizSubCategory = context.onboarding_card_details.business_subcategory.value;

  return [selectedBizCategory, selectedBizSubCategory];
}

export function doesHaveAdditionalDocs(context) {
  const bizCatSubCatPair = getBizCatSubCatPair(context).join('-');

  if (
    !!ADDITIONAL_DOCS_REQUIRED_REG_BIZ[bizCatSubCatPair] &&
    !isUnregisteredBusiness(context.business_overview.business_type.value)
  ) {
    return true;
  }
  return false;
}

export function getAdditionalDocCount(context) {
  const bizCatSubCatPair = getBizCatSubCatPair(context).join('-');

  if (ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair])
    return Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]).length;

  return 0;
}

//Todo: Need to use Lodash in dashboard codebase
function debounce(cb, time) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(cb, time, ...args);
  };
}
export { debounce };
