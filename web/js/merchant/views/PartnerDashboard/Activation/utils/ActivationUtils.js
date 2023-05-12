import { merchantFetch } from 'merchant/utils/ajax';
import * as Yup from 'yup';

const PRIVATE_LIMITED = 4;
const PUBLIC_LIMITED = 5;
const LLP = 6;
const PARTNERSHIP = 3;
const NOT_REGISTERED = 11;
const INDIVIDUAL = 2; // Legacy Type, Now combined  under Unregistered  Type
export const PROPRIETORSHIP = 1;
const NGO = 7; // 'NGO'
const TRUST = 9; // 'Trust'
const SOCIETY = 10; //  'Society'
const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};

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

export const getPincodeDetails = (pincode) => {
  return merchantFetch(`pincodes/${pincode}`)
    .then((response) => {
      if (response.data) {
        return {
          city: response.data.city,
          state_code: response.data.state_code,
        };
      }

      return null;
    })
    .catch((err) => {
      return err;
    });
};

export const addressDetailsSchema = () =>
  Yup.object().shape({
    business_registered_address: Yup.string().required(
      'Registered Business Address is a required field',
    ),
    business_registered_pin: Yup.string()
      .trim()
      .length(6, 'Registered Business Pincode must be 6 characters')
      .matches(/^[1-9][0-9]{5}$/, {
        message: 'Invalid Pincode',
        excludeEmptyString: true,
      })
      .required('Pincode is a required field'),
    business_registered_city: Yup.string().required('Registered Business City is a required field'),
    business_registered_state: Yup.string().required(
      'Registered Business State is a required field',
    ),
    business_operation_address: Yup.string().when('isOpAddressSameAsRegAddress', {
      is: true,
      then: Yup.string().trim().nullable(),
      otherwise: Yup.string().trim().required('Operational Business Address is a required field'),
    }),
    business_operation_pin: Yup.string().when('isOpAddressSameAsRegAddress', {
      is: true,
      then: Yup.string().trim().nullable(),
      otherwise: Yup.string()
        .trim()
        .length(6, 'Pincode must be 6 characters')
        .matches(/^[1-9][0-9]{5}$/, {
          message: 'Invalid Pincode',
          excludeEmptyString: true,
        })
        .required('Operational Business Pincode is a required field'),
    }),
    business_operation_city: Yup.string().when('isOpAddressSameAsRegAddress', {
      is: true,
      then: Yup.string().trim().nullable(),
      otherwise: Yup.string().required('Operational Business City is a required field'),
    }),
    business_operation_state: Yup.string().when('isOpAddressSameAsRegAddress', {
      is: true,
      then: Yup.string().trim(),
      otherwise: Yup.string().required('Operational Business State is a required field'),
    }),
  });

export const getAddressDetailsValidity = (addressDetails, formState) => {
  const {
    business_registered_address,
    business_registered_pin,
    business_registered_city,
    business_registered_state,
    business_operation_address,
    business_operation_pin,
    business_operation_city,
    business_operation_state,
  } = { ...addressDetails, ...formState };

  const isRegisteredAddressValid =
    business_registered_address &&
    business_registered_pin &&
    business_registered_city &&
    business_registered_state;

  const isOperationalAddressValid =
    business_operation_address &&
    business_operation_pin &&
    business_operation_city &&
    business_operation_state;

  return {
    isRegisteredAddressValid,
    isOperationalAddressValid,
  };
};
