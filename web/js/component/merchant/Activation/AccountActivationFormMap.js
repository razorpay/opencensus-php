import Input from 'component/Input';
import { getDetailsForIFSC } from 'common/util';
import { validatePANCard, validateIFSC } from 'rzp/utils/validators';

// This is as per the value saved in BE database
const PROPRIETORSHIP = 1;
const INDIVIDUAL = 2;
const PARTNERSHIP = 3;
const PRIVATE = 4; // 'Private Limited',
const PUBLIC = 5; // 'Public Limited',
const LLP = 6; // 'LLP'
const NGO = 7; // 'NGO'
//const Educational_Institute = 8 // Removed now
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'
const NOT_REGISTERED = 11; // 'Society'
//const Others = 12 // Removed now

// For marketplace linked account which required kyc
const needsKYC = activation => !!activation.props.data.need_kyc;

const businessFields = [
  {
    label: 'Business Name',
    name: 'business_name',
    info: 'Example: Acme Private Limited',
  },
  {
    label: 'Business Type',
    name: 'business_type',
    _cmp: Input.Select,
    options: [
      { label: 'Private Limited', name: PRIVATE },
      { label: 'Proprietorship', name: PROPRIETORSHIP },
      { label: 'Partnership', name: PARTNERSHIP },
      { label: 'Individual', name: INDIVIDUAL },
      { label: 'Not yet registered', name: NOT_REGISTERED },
      { label: 'Public Limited', name: PUBLIC },
      { label: 'LLP', name: LLP },
      { label: 'Trust', name: TRUST },
      { label: 'Society', name: SOCIETY },
      { label: 'NGO', name: NGO },
    ],
  },
  {
    label: 'Business PAN Details',
    name: 'company_pan',
    placeholder: 'PAN Number',
    info: 'PAN details should belong to the business mentioned above',
    validator: validatePANCard,
    _when: needsKYC,
  },
  {
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    name: 'promoter_pan',
    placeholder: 'PAN Number',
    validator: validatePANCard,
    _when: needsKYC,
  },
];

const bankAccountFields = [
  {
    name: 'bank_branch_ifsc',
    label: 'Branch IFSC Code',
    info: function(e) {
      if (!e) {
        return null;
      }

      return getDetailsForIFSC(e.target.value);
    },
    validator: validateIFSC,
  },
  {
    name: 'bank_account_number',
    label: 'Account Number',
    type: 'password',
    autoComplete: 'new-password',
    info: 'Your company account to which your payments will be settled',
    onFocus: e => {
      document.getElementsByName('bank_account_number')[0].type = 'text';
    },
    onBlur: e => {
      document.getElementsByName('bank_account_number')[0].type = 'password';
    },
  },
  {
    _name: 'account_no',
    label: 'Re-Enter Account Number',
    autoComplete: 'new-password',
    info: 'Please re-enter the bank account number.',
    onFocus: e => {
      document.querySelector('[data-name="account_no"]').type = 'text';
    },
    onBlur: e => {
      document.querySelector('[data-name="account_no"]').type = 'password';
    },
  },
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    info:
      'The beneficiary name should be same as the company name or individual name, in case of an LLP/Individual.',
  },
];

const uploadFields = [
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address",
    description:
      'Your Bank account number, IFSC code, and Company Name should be clearly visible',
  },
  {
    name: 'promoter_pan_url',
    label: 'PAN Card',
    description: 'Promoter/Individual PAN Card.',
  },
];

// Tabs name
export const accountFormTabs = [
  'Business Details',
  'Bank Account Details',
  'Documents Upload', // Needed only if need_kyc is true, so it will be removed before usage.
];

// Tabs content
export default [
  businessFields,
  bankAccountFields,
  uploadFields, // Needed only if need_kyc is true, so it will be removed before usage.
];
