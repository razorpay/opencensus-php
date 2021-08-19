const PRIVATE_LIMITED = 4;
const PUBLIC_LIMITED = 5;
const LLP = 6;
const PARTNERSHIP = 3;
const NOT_REGISTERED = 11; // 'Unregistered Businesses
const INDIVIDUAL = 2; // Legacy Type, Now combined under Unregistered Type
export const PROPRIETORSHIP = 1;
const NGO = 7; // 'NGO'
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];
const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};
const BusinessTypes = [PRIVATE_LIMITED, PUBLIC_LIMITED, LLP, PARTNERSHIP];

export const BusinessTypeOptions = [
  { label: '--Select--', name: '' },
  { label: 'Private Limited', name: PRIVATE_LIMITED },
  { label: 'Proprietorship', name: PROPRIETORSHIP },
  { label: 'Partnership', name: PARTNERSHIP },
  { label: 'Public Limited', name: PUBLIC_LIMITED },
  { label: 'LLP', name: LLP },
  { label: 'Trust', name: TRUST },
  { label: 'Society', name: SOCIETY },
  { label: 'NGO', name: NGO },
  { label: 'Not Registered', name: NOT_REGISTERED },
];

export function displayCompanyPAN(currentBusinessType) {
  return [INDIVIDUAL, NOT_REGISTERED, PROPRIETORSHIP].indexOf(Number(currentBusinessType)) === -1;
}

export function isUnregisteredBusiness(currentBusinessType) {
  return UNREGISTERED_TYPES[Number(currentBusinessType)];
}

export function getBusinessTypeInfo(businessType) {
  if (isUnregisteredBusiness(businessType)) {
    return "Unregistered business type is for freelancers or small businesses who have not yet registered as a company. Don't choose this option if your business is already registered. Business type cannot be changed once submitted.";
  }
  return '';
}

export function getBusinessNameInfo(businessType) {
  if (displayCompanyPAN(businessType)) {
    return 'We verify the details with the central PAN database. Please ensure you enter the correct PAN details';
  } else {
    return 'Example: Acme Infotech Private Limited';
  }
}

export function getAccountNumberInfo(businessType) {
  let text = '';
  if (isUnregisteredBusiness(businessType)) {
    text = 'Please ensure the Bank details you are entering are of the same person as the PAN.';
  } else {
    text =
      'Should be a current bank account of the company to which your payments will be settled.';
  }
  return text;
}

export function isValidIFSC(value) {
  return value && value.length === 11;
}

// Footer Buttons
const SUBMIT_CLARIFICATIONS = 'submit-clarifications';
const SAVE = 'save';
const SAVE_AND_NEXT = 'save-next';
const SUBMIT_L1_FORM = 'submit-L1-form';
const SUBMIT_KYC_FORM = 'submit-kyc-form';

export const FOOTER_BUTTONS = {
  SUBMIT_CLARIFICATIONS,
  SAVE,
  SAVE_AND_NEXT,
  SUBMIT_L1_FORM,
  SUBMIT_KYC_FORM,
};

export const LOADING = {
  ERROR: -1, // Error = show error msg
  SUCCESS: 1, // Success = show success msg
  PENDING: 0, // Pending = show spinner
  INITIAL: null, // Initial = hide spinner
  DEFAULT: 2, // Some custom message when form opens
};
