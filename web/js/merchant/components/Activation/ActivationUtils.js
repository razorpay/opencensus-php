/* eslint-disable */

import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { addPrefixToObjectKeys, isPresent } from 'common/utils/rzp-utils';
import QueryString from 'query-string';

import { BUSINESS_TYPE_OPTIONS } from './AccountActivationFormMap';
import {
  ADDITIONAL_DOCS_REQUIRED_REG_BIZ,
  DEFAULT_ADDITIONAL_DOC_REG_BIZ,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
  BIZ_CAT_SUB_CAT_OPTIONAL_ADDITIONAL_DOCS,
  BUSINESS_PROOF_TYPE_DOCS,
} from './Constants';

const PRIVATE_LIMITED = 4,
  PUBLIC_LIMITED = 5,
  LLP = 6,
  PARTNERSHIP = 3,
  NOT_REGISTERED = 11, // 'Unregistered Businesses
  INDIVIDUAL = 2, // Legacy Type, Now combined under Unregistered Type
  PROPRIETORSHIP = 1,
  NGO = 7, // 'NGO'
  TRUST = 9, // 'Trust'
  SOCIETY = 10, // 'Society'
  ORG_BusinessTypes = [NGO, TRUST, SOCIETY],
  UNREGISTERED_TYPES = {
    11: true,
    2: true,
  },
  BusinessTypes = [PRIVATE_LIMITED, PUBLIC_LIMITED, LLP, PARTNERSHIP];

const E_SIGN_AADHAR = [PROPRIETORSHIP, PARTNERSHIP, NOT_REGISTERED];

const bankAccountTabName = 'Bank Account';
const BANK_LIMIT_MESSAGE =
  "You have already changed your account 9 times, please ensure you enter the correct details this time as you won't be able to make any more changes after this attempt";

function differentAddress(activation) {
  return activation.state.same_address === '0';
}

function isUnregisteredBusiness(activation) {
  const currentBusinessType =
    activation.state.dirty['business_type'] || activation.props.data['business_type'];
  return UNREGISTERED_TYPES[Number(currentBusinessType)];
}

function excludeFor_Indiv(activation) {
  return !isUnregisteredBusiness(activation);
}

/* Include for Individual/Not Yet Registered */
function _showForIndiv(activation) {
  return isUnregisteredBusiness(activation);
}

function isL1Submitted(activation) {
  return activation.props.user.instantActivation.isL1Submitted;
}

function isL1Completed(activation) {
  const { user } = activation.props;
  if (user.isSubmitted) {
    return true; // Always return true if user.submitted true -> display all fields and tabs -> handles L2 submission without submitting L1 eg. Batch and EPOS
  }
  return isL1Submitted(activation) && !user.instantActivation.isBlacklistFlow;
}

function displayCompanyPAN(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;
  return [INDIVIDUAL, NOT_REGISTERED, PROPRIETORSHIP].indexOf(Number(currentBusinessType)) === -1;
}

function requiredForNGO(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return selectedBusinessType == NGO;
}

function showForOrgs(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;
  if (activation.props.user?.isLiteOnboarding) {
    return selectedBusinessType && Number(selectedBusinessType) === NGO;
  }
  return selectedBusinessType && ORG_BusinessTypes.indexOf(Number(selectedBusinessType)) !== -1;
}

function isPANVerified(activation) {
  return (
    (isUnregisteredBusiness(activation) || activation.props.user.isSyncExperimentEnabled) &&
    activation.props.data.poi_verification_status === 'verified'
  );
}

function isCompanyPANVerified(activation) {
  return (
    activation.props.user.isSyncExperimentEnabled &&
    activation.props.data.company_pan_verification_status === 'verified'
  );
}

function checkValidityFromAPI(data, key, errValue, errorMsg) {
  return data && (data[key] === errValue || data[key] === 'not_matched') ? errorMsg : '';
}

function getPANDescription(data) {
  const { poi_verification_status } = data;
  const description =
    'We verify the details with the central PAN database. Please ensure you enter the correct details';
  return poi_verification_status == 'incorrect_details' || poi_verification_status == 'not_matched'
    ? ''
    : description;
}

function getBeneficiaryInfo(value) {
  const currentBusinessType = this.state.dirty.business_type || this.props.data.business_type;
  if (currentBusinessType == PROPRIETORSHIP) {
    return 'Bank A/C Beneficiary Name should be the same as the name on Authorised Signatory PAN or Business PAN.';
  }
  if (isUnregisteredBusiness(this)) {
    return 'Bank Account beneficiary name should be same as the name on your PAN Card.';
  }
  if (BusinessTypes.includes(Number(currentBusinessType))) {
    return 'Bank A/c Beneficiary Name should be the same as Business Pan Name.';
  }
  return 'Bank A/C Beneficiary Name should be the same as the name on Authorised Signatory PAN or Business PAN.';
}

function getBillingLabelInfo() {
  let text = '';
  if (isUnregisteredBusiness(this)) {
    text = 'Enter the brand name your customers are familiar with or you want to use in future.';
  } else {
    text =
      'The brand name that your customers are familiar with. It should either be similar to your registered business name or website name.';
  }
  return text;
}

function getBusinessTypeInfo() {
  if (isUnregisteredBusiness(this)) {
    return "Unregistered business type is for freelancers or small businesses who have not yet registered as a company. Don't choose this option if your business is already registered. Business type cannot be changed once submitted.";
  }
}

function getAccountNumberInfo() {
  let text = '';
  if (isUnregisteredBusiness(this)) {
    text = 'Please ensure the Bank details you are entering are of the same person as the PAN.';
  } else {
    text =
      'Should be a current bank account of the company to which your payments will be settled.';
  }
  return text;
}

function getBusinessNameInfo() {
  if (displayCompanyPAN(this) && this.props.user.isRegAutoKYCEnabled) {
    return 'We verify the details with the central PAN database. Please ensure you enter the correct PAN details';
  } else {
    return 'Example: Acme Infotech Private Limited';
  }
}

function hasSelectedBlacklistedCategory(activation) {
  const { props, state } = activation;
  const categories = props.categories;
  if (isPresent(categories)) {
    const selectedCategory = state.dirty.business_category || props.data.business_category;
    const subcategories = selectedCategory && categories[selectedCategory]?.subcategories;
    if (isPresent(subcategories)) {
      const selectedSubcategory =
        state.dirty.business_subcategory || props.data.business_subcategory;
      return (
        subcategories[selectedSubcategory] &&
        (isUnregisteredBusiness(activation)
          ? subcategories[selectedSubcategory]['non_registered_activation_flow'] === 'blacklist'
          : subcategories[selectedSubcategory]['activation_flow'] === 'blacklist')
      );
    }
  }
  return false;
}

// Additional Docs required for only some Reg. Biz based on Biz Cat. and Biz Subcat.
function doesHaveAdditionalDocs(activation, bizCatSubCatPair) {
  if (!bizCatSubCatPair) {
    bizCatSubCatPair = getBizCatSubCatPair(activation.state, activation.props);
  }

  const additionalDocReqMapKey = getValuesSeparatedBySymbol(bizCatSubCatPair, '-');

  if (
    !!ADDITIONAL_DOCS_REQUIRED_REG_BIZ[additionalDocReqMapKey] &&
    !isUnregisteredBusiness(activation)
  ) {
    return true;
  }

  return false;
}

function getDefaultAdditionalDoc(activation, bizCatSubCatPair) {
  if (!bizCatSubCatPair) {
    bizCatSubCatPair = getBizCatSubCatPair(activation.state, activation.props);
  }

  const defaultAdditionalDocMapKey = getValuesSeparatedBySymbol(bizCatSubCatPair, '-');

  const allDocs = Object.keys(activation.props.data.documents);
  const allAdditionalDocs = Object.keys(
    ADDITIONAL_DOCS_LABEL_VALUE_MAP[defaultAdditionalDocMapKey],
  );
  const hasUploadedAdditionalDocs = allAdditionalDocs.filter((doc) => allDocs.includes(doc));

  if (hasUploadedAdditionalDocs.length > 0) return hasUploadedAdditionalDocs[0];

  return DEFAULT_ADDITIONAL_DOC_REG_BIZ[defaultAdditionalDocMapKey];
}

function getAdditionalDocOptions(activation, bizCatSubCatPair) {
  if (!bizCatSubCatPair) {
    bizCatSubCatPair = getBizCatSubCatPair(activation.state, activation.props);
  }

  const additionalDocsMapKey = getValuesSeparatedBySymbol(bizCatSubCatPair, '-');
  const additionalDoc = ADDITIONAL_DOCS_LABEL_VALUE_MAP[additionalDocsMapKey];

  return Object.keys(additionalDoc).map((c) => {
    const doc = additionalDoc[c];
    return {
      name: c,
      label: doc.label,
    };
  });
}

function getValuesSeparatedBySymbol(values = [], symbol = '-') {
  return values.join(symbol);
}

function getBizCatSubCatPair(state, props) {
  const selectedBizCategory = state.dirty.business_category || props.data.business_category;
  const selectedBizSubCategory =
    state.dirty.business_subcategory || props.data.business_subcategory;

  return [selectedBizCategory, selectedBizSubCategory];
}

function getAdditionalDocCount(state, props) {
  const bizCatSubCatPair = getBizCatSubCatPair(state, props);
  const additionalDocsMapKey = getValuesSeparatedBySymbol(bizCatSubCatPair, '-');

  if (ADDITIONAL_DOCS_LABEL_VALUE_MAP[additionalDocsMapKey])
    return Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[additionalDocsMapKey]).length;

  return 0;
}

function isOptionalAdditionalDoc(docKey, bizCatSubCatKey) {
  return !!(
    BIZ_CAT_SUB_CAT_OPTIONAL_ADDITIONAL_DOCS[docKey] &&
    BIZ_CAT_SUB_CAT_OPTIONAL_ADDITIONAL_DOCS[docKey][bizCatSubCatKey]
  );
}

function isAdditonalDocRequired(state, props) {
  const { additional_doc } = state;
  const bizCatSubCatPair = getBizCatSubCatPair(state, props);
  const bizCatSubCatKey = getValuesSeparatedBySymbol(bizCatSubCatPair, '-');

  return !isOptionalAdditionalDoc(additional_doc, bizCatSubCatKey);
}

function hasAPIL1Error({ poi_verification_status, is_unreg }) {
  if (
    is_unreg &&
    (poi_verification_status === 'incorrect_details' || poi_verification_status === 'not_matched')
  ) {
    return true;
  }
  return false;
}

function isSourceRX() {
  const query = QueryString.parse(window.location.search);
  const isRXActivation = !!(query && query.merchant && query.merchant === 'x');
  return isRXActivation;
}

function showSubcategory(activation) {
  let { state, props } = activation;
  let showSubcategory = false;
  const nc_flow = props.data.activation_status === 'needs_clarification';

  let businessCategory =
    state.dirty.business_category != null
      ? state.dirty.business_category
      : props.data.business_category;

  if (businessCategory) {
    showSubcategory = businessCategory !== 'others';
    let subcategories = props.categories[businessCategory]?.subcategories;
    if (Object.keys(subcategories).length === 1 && !nc_flow) {
      showSubcategory = false;
    }
  }
  return showSubcategory;
}

function removeArrayDuplicatesByProp(originalArray, prop) {
  var newArray = [];
  var uniqueObject = {};

  for (var i in originalArray) {
    uniqueObject[originalArray[i][prop]] = originalArray[i];
  }

  for (i in uniqueObject) {
    newArray.push(uniqueObject[i]);
  }
  return newArray;
}

function doesHaveBusinessProofDocs(activation) {
  const businessType =
    Number(activation.state.dirty.business_type) || Number(activation.props.data.business_type);
  return businessType === 1;
}

function getDefaultBusinessProofDoc(activation) {
  let defaultBusinessProofDoc = '';
  const documents = activation.props.data.documents;
  if (documents.gst_certificate && documents.gst_certificate.length) {
    defaultBusinessProofDoc = 'gst_certificate';
  } else if (
    documents.shop_establishment_certificate &&
    documents.shop_establishment_certificate.length
  ) {
    defaultBusinessProofDoc = 'shop_establishment_certificate';
  } else {
    defaultBusinessProofDoc = 'msme_certificate';
  }
  return defaultBusinessProofDoc;
}

function hasUploadedBusinessProofUrl(activation) {
  const documents = activation.props.data.documents;
  return !!(documents && documents.business_proof_url && documents.business_proof_url.length);
}

function hasUploadedBusinessProofTypeDoc(activation) {
  const documents = activation.props.data.documents;
  return Object.keys(BUSINESS_PROOF_TYPE_DOCS).some((key) => isPresent(documents[key]));
}

function isBusinessProofTypeDocFieldVisible(activation) {
  const currentBusinessType =
    Number(activation.state.dirty.business_type) || Number(activation.props.data.business_type);
  if (currentBusinessType === PROPRIETORSHIP) {
    if (
      !(hasUploadedBusinessProofUrl(activation) && activation.props.data.submitted) ||
      hasUploadedBusinessProofTypeDoc(activation)
    ) {
      return true;
    }
  }
  return false;
}

function canShowEAadharComponent(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  if (E_SIGN_AADHAR.includes(Number(currentBusinessType)) && activation.props.user.isOrgRZP) {
    return true;
  }
  return false;
}

function canShowCustomGstinField(activation) {
  const {
    props: { gstinDetails },
  } = activation;
  const gstinList = gstinDetails?.gstinList;
  if (
    activation.props.user.isGstinAutoPopulate &&
    !activation.isOnKYCTab() &&
    gstinList &&
    Array.isArray(gstinList)
  ) {
    return true;
  }
  return false;
}

function isDedupe(activation) {
  if (activation.dedupe && Object.keys(activation.dedupe).length) {
    const { isMatch, isUnderReview } = activation.dedupe;
    if (isMatch) {
      if (isUnderReview) {
        return 'partial';
      }
      return 'blocked';
    }
    return 'passed';
  }
  return 'passed';
}

function isDedupeOldFunc(activation) {
  if (
    !!activation.locked &&
    activation.activation_status === 'under_review' &&
    activation.isDedupe
  ) {
    return true;
  }
  return false;
}

function showAadharDoc(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;
  if (
    E_SIGN_AADHAR.includes(Number(currentBusinessType)) &&
    activation.state.isAadharDocVisible &&
    !!activation.props.user.isOrgRZP &&
    !activation.props.user.needsClarification
  ) {
    return false;
  }
  return true;
}

function getActivationState(activationData = {}, isUnregisteredBusiness) {
  let activationState;

  const {
    business_website,
    canGenerateTnCPage,
    activation_status,
    activation_form_milestone,
    poi_verification_status,
    activated,
    activationStatusChangeLogs,
    merchant,
    isHardLimitReached,
    merchant_tnc,
  } = activationData;

  const tncRequired = !business_website && !merchant_tnc && !isSourceRX() && canGenerateTnCPage;

  const dedupeStatus = isDedupe(activationData);

  if (activation_status === 'activated' && !isHardLimitReached) {
    activationState = 'account_activated';
  } else if (!activation_form_milestone) {
    activationState = 'L1_Start';
  } else if (activation_form_milestone === 'L1') {
    if (dedupeStatus === 'blocked') {
      activationState = 'L1_dedupe_blocked';
    } else if (dedupeStatus === 'passed' || dedupeStatus === 'partial') {
      if (activation_status === 'instantly_activated') {
        activationState = 'L1_instantly_activated';
      } else if (isUnregisteredBusiness) {
        if (poi_verification_status === 'initiated') {
          activationState = 'poi_initiated';
        } else if (poi_verification_status === 'verified' && activated) {
          activationState = 'poi_verified';
        } else activationState = 'poi_failed';
      } else activationState = 'payment_disabled';
    }
  } else if (activation_form_milestone === 'L2') {
    if (dedupeStatus === 'blocked') {
      activationState = 'L2_dedupe_blocked';
    } else if (activation_status === 'under_review') {
      if (dedupeStatus === 'partial') {
        activationState = tncRequired
          ? 'under_review_without_tnc_partial'
          : 'under_review_with_tnc_partial';
      } else if (activationStatusChangeLogs.includes('needs_clarification')) {
        activationState = tncRequired ? 'under_review_without_tnc' : 'under_review_with_tnc';
      } else if (dedupeStatus === 'passed') {
        if (tncRequired) {
          activationState = activated
            ? 'under_review_without_tnc_passed'
            : 'under_review_without_tnc';
        } else {
          activationState = activated ? 'under_review_with_tnc_passed' : 'under_review_with_tnc';
        }
      }
    } else if (activation_status === 'needs_clarification') {
      if (activationStatusChangeLogs.includes('activated_mcc_pending')) {
        activationState = merchant.hold_funds
          ? 'needs_clarification_funds_on_hold'
          : 'needs_clarification_mcc_pending';
      } else activationState = 'needs_clarification';
    } else if (activation_status === 'activated_mcc_pending') {
      activationState = tncRequired
        ? 'activated_mcc_pending_without_tnc'
        : 'activated_mcc_pending_with_tnc';
    } else if (activation_status === 'rejected') {
      activationState = 'rejected';
    } else if (merchant.hold_funds && isHardLimitReached) {
      activationState = 'funds_on_hold';
    }
  }

  return activationState;
}

const getBankTabHeader = (businessType) => {
  let title = bankAccountTabName;
  let subtitle = '';
  if (
    [PRIVATE_LIMITED, PUBLIC_LIMITED, LLP, PARTNERSHIP, NGO, TRUST, SOCIETY].includes(businessType)
  ) {
    title = 'Company Bank Account';
    subtitle = `Enter Bank Account details of your company's bank account. Your KYC will be rejected if you enter personal bank account details.`;
  } else if (businessType === PROPRIETORSHIP) {
    title = 'Company or Authorised Signatory Bank Account';
    subtitle = 'Enter Bank Account details of your company or authorised signatory.';
  } else if (UNREGISTERED_TYPES[businessType]) {
    title = 'Personal Bank account';
    subtitle = 'Enter your personal bank account details.';
  }
  return { title, subtitle };
};

const isPanVerificationFailed = (poiStatus, companyPanStatus) => {
  return (
    poiStatus === 'incorrect_details' ||
    poiStatus === 'not_matched' ||
    companyPanStatus === 'incorrect_details' ||
    companyPanStatus === 'not_matched'
  );
};

const getBankVerificationAtteemptError = (activation) => {
  if (
    activation.props.data.bank_details_verification_status &&
    !['initiated', 'verified'].includes(activation.props.data.bank_details_verification_status) &&
    activation.props.user.isSyncBankVerificationEnabled
  ) {
    if (activation.props.bvsApiCount == 9) return BANK_LIMIT_MESSAGE;
  }
  return '';
};

export {
  differentAddress,
  isUnregisteredBusiness,
  excludeFor_Indiv,
  UNREGISTERED_TYPES,
  _showForIndiv,
  isL1Submitted,
  isL1Completed,
  displayCompanyPAN,
  requiredForNGO,
  showForOrgs,
  isPANVerified,
  checkValidityFromAPI,
  getPANDescription,
  getBeneficiaryInfo,
  getBillingLabelInfo,
  getBusinessTypeInfo,
  getAccountNumberInfo,
  getBusinessNameInfo,
  hasSelectedBlacklistedCategory,
  doesHaveAdditionalDocs,
  getDefaultAdditionalDoc,
  getAdditionalDocOptions,
  getAdditionalDocCount,
  isAdditonalDocRequired,
  hasAPIL1Error,
  showSubcategory,
  isSourceRX,
  isDedupe,
  removeArrayDuplicatesByProp,
  doesHaveBusinessProofDocs,
  getDefaultBusinessProofDoc,
  hasUploadedBusinessProofUrl,
  hasUploadedBusinessProofTypeDoc,
  isBusinessProofTypeDocFieldVisible,
  canShowEAadharComponent,
  showAadharDoc,
  getActivationState,
  isDedupeOldFunc,
  getBankTabHeader,
  isCompanyPANVerified,
  isPanVerificationFailed,
  canShowCustomGstinField,
  getBankVerificationAtteemptError,
};
