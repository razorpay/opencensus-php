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
      { label: '--Select--', name: '0' },
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
  [
    {
      name: 'bank_account_number',
      label: 'Account Number',
      info:
        'Should be a current bank account of the company to which your payments will be settled.',
      autoComplete: 'new-password',
      type: 'password',
      onFocus: e => {
        document.getElementsByName('bank_account_number')[0].type = 'text';
      },
      onBlur: function(e) {
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
      onPaste: function(e) {
        e.preventDefault();
      }, // Disable copy-paste in this field
      onFocus: e => {
        document.querySelector('[data-name="account_no"]').type = 'text';
      },
      onBlur: e => {
        document.querySelector('[data-name="account_no"]').type = 'password';
      },
      validator: function(value) {
        const bankAccountNo = this.state.dirty.bank_account_number;
        if (!value) {
          return;
        }

        if (bankAccountNo && value !== bankAccountNo) {
          // Something changed in main 'bank account' field
          return 'Account no. does not match';
        }
      },
    },
  ],
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    info: function() {
      const currentBusinessType =
        this.state.dirty.business_type || this.props.data.business_type;
      if ([LLP, INDIVIDUAL].indexOf(Number(currentBusinessType)) !== -1) {
        return 'The beneficiary name should be same as Individual name';
      }

      return 'The beneficiary name should be same as the Company name';
    },
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
let tabsData;
export default (tabsData = [
  businessFields,
  bankAccountFields,
  uploadFields, // Needed only if need_kyc is true, so it will be removed before usage.
]);

/* Note: This works only when FORM_TAB_CONTENT is has not splied out anything from middle */
export const fieldNameMeta = (function() {
  const formNames = [];

  for (let t = 0; t < tabsData.length; t++) {
    const tabNames = [];
    tabsData[t].forEach(f => {
      if (Array.isArray(f)) {
        return f.forEach(gf => {
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
