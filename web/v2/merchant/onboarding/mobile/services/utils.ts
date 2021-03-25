import axios from 'axios';
import { isVisible } from 'v2/merchant/onboarding/mobile/context/store';
import {
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
  UNREGISTERED_TYPES,
  BUSINESS_PROOF_TYPE_DOCS,
  ORG_BusinessTypes,
  PROPRIETORSHIP,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
  ADDITIONAL_DOCS_REQUIRED_REG_BIZ,
  ADDRESS_PROOF_TYPES,
  BANK_PROOF_TYPE_DOC,
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
  if (onboarding_milestone === 'L1' || onboarding_milestone === 'L2') {
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
  if (+data.business_overview.business_type.value === PROPRIETORSHIP) {
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

function onScreenDocuments(data) {
  const { addressDoc, businessDoc, bankDoc, additionalDoc } = data;

  return Object.entries(data.documents)
    .filter((item: any) => isVisible(item[0], data))
    .reduce((prevValue: any, currValue: any[]) => {
      const [key, value] = currValue;
      switch (key) {
        case `${addressDoc}_front`:
        case `${addressDoc}_back`:
        case 'business_proof_url':
        case 'business_pan_url':
        case 'personal_pan':
        case bankDoc:
        case businessDoc:
        case 'form_80g_url':
        case 'form_12a_url':
        case additionalDoc:
          return { ...prevValue, [key]: value };
        case 'shop_establishment_number':
          if (businessDoc === 'shop_establishment_certificate')
            return { ...prevValue, [key]: value };
          break;
        default:
          break;
      }
      return prevValue;
    }, {});
}

export function isDocmentTabComplete(data) {
  const tabData = { ...onScreenDocuments(data) };

  return Object.keys(tabData).every((key) => {
    return !!tabData[key].value && !tabData[key].error;
  });
}

export function getDefaultSelectedDocs(context, type) {
  const documents = context.documents;

  const bizCatSubCatPair = getBizCatSubCatPair(context).join('-');
  const defaultAdditionalDoc = ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]
    ? Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair])[0]
    : '';

  let defaultSelectedDoc;

  switch (type) {
    case 'address':
      defaultSelectedDoc = Object.keys(ADDRESS_PROOF_TYPES).filter(
        (key) => documents[`${key}_front`].value && documents[`${key}_back`],
      );
      defaultSelectedDoc = defaultSelectedDoc.length ? defaultSelectedDoc : ['aadhar'];
      break;
    case 'bank':
      defaultSelectedDoc = Object.keys(BANK_PROOF_TYPE_DOC).filter((key) => documents[key].value);
      defaultSelectedDoc = defaultSelectedDoc.length ? defaultSelectedDoc : ['cancelled_cheque'];
      break;
    case 'business':
      defaultSelectedDoc = Object.keys(BUSINESS_PROOF_TYPE_DOCS).filter(
        (key) => documents[key].value,
      );
      defaultSelectedDoc = defaultSelectedDoc.length ? defaultSelectedDoc : ['gst_certificate'];
      break;
    default:
      defaultSelectedDoc = ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]
        ? Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]).filter(
            (key) => documents[key].value,
          )
        : [];
      defaultSelectedDoc = defaultSelectedDoc.length ? defaultSelectedDoc : [defaultAdditionalDoc];
      break;
  }
  return defaultSelectedDoc[0];
}

export const autoPrefixUrls = (url: string) => {
  const regex = /^https?:\/\//i;
  if (!url || url.length === 0) {
    return url;
  }
  const tempUrl = url.toLowerCase();
  if (!regex.test(tempUrl)) {
    url = `http://${url}`;
  }
  return url;
};

/*
 * Helper fn. to fetch IFSC bank details for IFSC code entered in field
 * */
export function getDetailsForIFSC(ifscCode) {
  if (ifscCode.length !== 11) {
    return null;
  }

  return axios(`https://ifsc.razorpay.com/${ifscCode}`).then((info: any) => {
    info = info.data;
    if (info) {
      info = {
        bank: info.BANK,
        branch: info.BRANCH,
      };
      return info;
    }
    return null; // Invalid IFSC code
  });
}

export function getPoiVerificationStatus(context) {
  return (
    context &&
    isUnregisteredBusiness(context.business_overview.business_type.value) &&
    (context.poi_verification_status === 'incorrect_details' ||
      context.poi_verification_status === 'not_matched')
  );
}

export function isBusinessProofUrlVisible(context) {
  if (!isUnregisteredBusiness(context.business_overview.business_type.value)) {
    if (
      +context.business_overview.business_type.value === PROPRIETORSHIP &&
      hasUploadedBusinessProofTypeDoc(context.documents)
    ) {
      return false;
    }
    if (
      +context.business_overview.business_type.value !== PROPRIETORSHIP ||
      (hasUploadedBusinessProofUrl(context.documents) && context.submitted)
    ) {
      return true;
    }
  }
  return false;
}

export function isBusinessPanVisible(context) {
  return (
    !isUnregisteredBusiness(context.business_overview.business_type.value) &&
    +context.business_overview.business_type.value !== PROPRIETORSHIP
  );
}

export function isPersonalPanVisible(context) {
  return (
    !isUnregisteredBusiness(context.business_overview.business_type.value) &&
    +context.business_overview.business_type.value === PROPRIETORSHIP
  );
}

export function hasSelectedBlacklistCategory(context, businessCategory) {
  const blacklistCategory: any = [];

  businessCategory.forEach((item) => {
    item.matches.forEach((_item) => {
      if (_item.subcategory_value === context.business_subcategory) {
        blacklistCategory.push(_item);
      }
    });
  });

  return (
    isUnregisteredBusiness(context.business_type) &&
    blacklistCategory.length &&
    blacklistCategory[0].non_registered_activation_flow === 'blacklist'
  );
}

export function getDocumentTitle(context) {
  const businessType = context.business_overview.business_type.value;

  switch (businessType) {
    case '3':
      return 'Partnership Deed';
    case '7':
      return 'NGO Registeration Certificate';
    case '9':
      return 'Trust Registeration Certificate';
    case '10':
      return 'Society Registeration Certificate';
    default:
      return 'Certificate of Incorporation';
  }
}

export const checkIfDedupe = (data) => {
  return data && !!data.locked && !data.activated && data.merchant.hold_funds;
};
export function checkIfEAadharStepCompleted(data) {
  let isEAadharFieldFilled = false;
  if (
    (data && data.stakeholder && data.stakeholder.aadhaar_esign_status === 'verified') ||
    (data.stakeholder && data.stakeholder.aadhaar_linked == '0')
  ) {
    isEAadharFieldFilled = true;
  }
  return isEAadharFieldFilled;
}
