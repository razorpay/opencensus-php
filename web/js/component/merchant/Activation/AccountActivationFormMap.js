import Input from 'component/Input';
import { getDetailsForIFSC } from 'common/util';

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
      '',
      'Proprietorship',
      'Individual',
      'Partnership',
      'Private Limited',
      'Public Limited',
      'LLP',
      'NGO',
      'Educational Institutes',
      'Trust',
      'Society',
      'Not yet registered',
      'Other',
    ],
  },
  {
    label: 'Business PAN Details',
    name: 'company_pan',
    placeholder: 'PAN Number',
    info: 'PAN details should belong to the business mentioned above',
    _when: needsKYC,
  },
  {
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    name: 'promoter_pan',
    placeholder: 'PAN Number',
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
  },
  {
    name: 'bank_account_number',
    label: 'Account Number',
    type: 'password',
    info: 'Your company account to which your payments will be settled',
  },
  {
    _name: 'account_no',
    label: 'Re-Enter Account Number',
  },
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    description:
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
