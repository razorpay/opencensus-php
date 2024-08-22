import Input from 'common/new-ui/Input';
import { getDetailsForIFSC } from 'common/utils/rzp-utils';
import { validatePANCard, validateIFSC } from 'common/utils/validators';

// This is as per the value saved in BE database
const PROPRIETORSHIP = 1;
const INDIVIDUAL = 11;
const PARTNERSHIP = 3;
const PRIVATE = 4; // 'Private Limited',
const PUBLIC = 5; // 'Public Limited',
const LLP = 6; // 'LLP'
const NGO = 7; // 'NGO'
//const Educational_Institute = 8 // Removed now
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'

// For marketplace linked account which required kyc
const needsKYC = (activation) => !!activation.props.data.need_kyc;

export const BUSINESS_TYPE_OPTIONS = [
  { label: '--Select--', name: '' },
  { label: 'Private Limited', name: PRIVATE },
  { label: 'Proprietorship', name: PROPRIETORSHIP },
  { label: 'Partnership', name: PARTNERSHIP },
  { label: 'Individual', name: INDIVIDUAL },
  { label: 'Public Limited', name: PUBLIC },
  { label: 'LLP', name: LLP },
  { label: 'Trust', name: TRUST },
  { label: 'Society', name: SOCIETY },
  { label: 'NGO', name: NGO },
];

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
    options: BUSINESS_TYPE_OPTIONS,
  },
  {
    label: 'Business PAN Number',
    name: 'company_pan',
    placeholder: 'PAN Number',
    info: 'PAN details should belong to the business mentioned above',
    className: 'Input--capitalize',
    validator: validatePANCard,
    _when: needsKYC,
  },
  {
    label: 'PAN info of Authorized Signatory/Promoter/Director',
    name: 'promoter_pan',
    placeholder: 'PAN Number',
    className: 'Input--capitalize',
    validator: validatePANCard,
    _when: needsKYC,
  },
];

const bankAccountFields = [
  {
    name: 'bank_branch_ifsc',
    label: 'Branch IFSC Code',
    info: function (e) {
      if (!e) {
        return null;
      }

      return getDetailsForIFSC(e.target.value);
    },
    validator: validateIFSC,
  },
  [
    {
      name: 'bank_account_number',
      label: 'Account Number',
      info: 'Should be a current bank account of the company to which your payments will be settled.',
      autoComplete: 'new-password',
      type: 'password',
      onPaste: function (e) {
        e.preventDefault();
      }, // Disable copy-paste in this field
      onFocus: (e) => {
        document.getElementsByName('bank_account_number')[0].type = 'text';
      },
      onBlur: function (e) {
        document.getElementsByName('bank_account_number')[0].type = 'password';

        const bankAccountNumber = this.state.dirty.bank_account_number;
        const accountNo = this.state.account_no;

        const isMatching = bankAccountNumber && bankAccountNumber == accountNo;

        if ((!!bankAccountNumber && !accountNo) || !isMatching) {
          document.querySelector('[data-name="account_no"]').focus(); // Focus on dependent field on Blur. Will be ignored if that is disabled.
        }
      },
    },
    {
      _name: 'account_no',
      label: 'Re-Enter Account Number',
      type: 'password',
      required: false,
      autoComplete: 'new-password',
      info: 'Please re-enter the bank account number.',
      _autoRenderImpure: true, // Re-render to show the error
      onPaste: function (e) {
        e.preventDefault();
      }, // Disable copy-paste in this field
      onFocus: (e) => {
        document.querySelector('[data-name="account_no"]').type = 'text';
      },
      onBlur: (e) => {
        document.querySelector('[data-name="account_no"]').type = 'password';
      },
      validator: function (value) {
        if (!value) {
          return;
        }
        const bankAccountNo = this.state.dirty.bank_account_number;

        if (bankAccountNo && value !== bankAccountNo) {
          // Something changed in main 'bank account' field
          return 'Account no. does not match';
        }
      },
      _when: (activation) => {
        const isLocked = activation.props.data.locked;

        return !isLocked;
      },
    },
  ],
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    maxLength: '120',
    minLength: '4',
    info: function () {
      const currentBusinessType = this.state.dirty.business_type || this.props.data.business_type;

      let text = 'Company';

      if ([LLP, INDIVIDUAL].indexOf(Number(currentBusinessType)) !== -1) {
        text = 'Individual';
      }

      return `The beneficiary name should be same as ${text} name`;
    },
  },
];

const uploadFields = [
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address",
    description: 'Your Bank account number, IFSC code, and Company Name should be clearly visible',
    _cmp: Input.File,
  },
  {
    name: 'promoter_pan_url',
    label: 'PAN Card',
    description: 'Promoter/Individual PAN Card.',
    _cmp: Input.File,
  },
];

// Tabs name
export const accountFormTabs = [
  'Business Details',
  'Bank Account Details',
  'Documents Upload', // Needed only if need_kyc is true, so it will be removed before usage.
];

// Tabs content
const tabsData = [
  businessFields,
  bankAccountFields,
  uploadFields, // Needed only if need_kyc is true, so it will be removed before usage.
];

/*
 * Note: If some Form Tab is removed from `tabsData`, then it's corresponding fields must also be removed from formNamesMeta
 * The same you can check for data.need_kyc LA accounts
 */
export const accountFormFieldNamesMeta = (function () {
  const formNames = [];

  for (let t = 0; t < tabsData.length; t++) {
    const tabNames = [];
    tabsData[t].forEach((f) => {
      if (Array.isArray(f)) {
        return f.forEach((gf) => {
          // groups fields are array.
          if (gf.name) {
            tabNames.push(gf.name); // Check if this field has name attribute
          }
        });
      } else if (f.name) {
        // Check if the field has name attribute
        tabNames.push(f.name);
      }
    });

    formNames.push(tabNames);
  }

  return formNames;
})();

export default tabsData;
