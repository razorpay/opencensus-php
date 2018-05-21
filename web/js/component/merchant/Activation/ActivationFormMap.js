import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

import { getDetailsForIFSC } from 'common/util';
import { isValidGSTIN } from 'rzp/utils/rzp-utils';
import {
  validateCIN,
  validateIFSC,
  validatePANCard,
} from 'rzp/utils/validators';

const NGO_BUSINESS_TYPE = 7;
const differentAddress = activation => activation.state.same_address === '0';

const CIN_BusinessTypes = [
  4, // 'Private Limited',
  5, // 'Public Limited',
  11, // 'Not yet registered',
];

const LLPIN_BusinessTypes = [
  6, // 'LLP'
];

const FORM_BusinessTypes = [
  7, // 'NGO'
  9, // 'Trust'
  10, // 'Society'
];

const stateOptions = [''].concat(
  Object.keys(states).map(c => {
    return {
      name: c,
      label: states[c],
    };
  })
);

const contactFields = [
  {
    label: 'Contact Name',
    name: 'contact_name',
  },
  {
    label: 'Contact Number',
    name: 'contact_mobile',
    type: 'tel',
    addonBefore: '+91',
    info: "We'll reach out on this number for any account related issues.",
  },
  {
    label: 'Contact Email',
    name: 'contact_email',
    type: 'email',
    info: "We'll reach out to this email for any account related issues.",
  },
];

const businessModel = [
  {
    label: 'Business Name',
    name: 'business_name',
    info: 'Example: Acme Private Limited',
  },
  {
    label: 'Billing Label',
    name: 'business_dba',
    info:
      'The brand name that your customers are familiar with. It should either be similar to your registered name or website name. It will appear on payment screen, emails and more.',
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
  [
    {
      label: 'Business Category',
      name: 'business_category',
      _cmp: Input.Select,
      options: [],
    },
    {
      label: 'Business Model',
      name: 'business_model',
      info:
        'Please give a brief explanation of your business model and future plans',
      _cmp: Input.Textarea,
      _when: activation => {
        let { state, props } = activation;

        let businessCategory =
          state.dirty.business_category != null
            ? state.dirty.business_category
            : props.data.business_category;

        return businessCategory === 'others'; // If businessCategory is selected to others, then Business Model is to be filled
      },
    },
    {
      label: 'Sub Category',
      name: 'business_subcategory',
      _cmp: Input.Select,
      options: [],
      _optionsFn: function(activation, categories) {
        // For setting options dynamically on basis some condition or other field selection
        const userSelection =
          activation.state.dirty.business_category ||
          activation.props.data.business_category;

        if (userSelection && categories[userSelection]) {
          const subCategories = categories[userSelection].subcategories;

          this.options = [''].concat(
            Object.keys(subCategories).map(c => ({
              name: c,
              label: subCategories[c],
            }))
          );
        }

        return this.options;
      },
      _when: activation => {
        let { state, props } = activation;
        let hasBusinessCategory = false;

        let businessCategory =
          state.dirty.business_category != null
            ? state.dirty.business_category
            : props.data.business_category;

        if (businessCategory) {
          hasBusinessCategory = businessCategory !== 'others';
        }

        // 'Others' business_category has no sub_category
        return hasBusinessCategory;
      },
    },
  ],
  {
    label: () => (
      <span>
        We want to accept <b>International Payments</b> as well
      </span>
    ),
    name: 'business_international',
    _cmp: Input.Check,
    required: false,
    description:
      'We’ll reach out to you as we might require some additional information to avail this feature. Please note that the application for international payments takes longer than usual to process.',
  },
  {
    label: 'Business Website/App',
    name: 'business_website',
    placeholder: 'Enter URL',
    type: 'url',
    description: (
      <React.Fragment>
        Your website should have following information easily accessible:
        <b> About Us</b>,<b> Contact</b>,<b> Privacy Policy</b>,
        <b> Terms & Conditions</b>, <b>Refund Policy</b> & <b>Pricing</b>.
        Please refer our{' '}
        <a href="" target="_blank">
          Compliance Policies{' '}
        </a>
        for more details.
      </React.Fragment>
    ),
    info: 'Example: https://www.company.com',
  },
];

const registrationDetails = [
  {
    label: 'CIN',
    name: 'company_cin',
    validator: validateCIN,
    required: true, // It's mandatory only for certain orgs
    info:
      'Mandatory for Companies. Example : U 67190 TN 2014 PTC 096978 (no spaces)',
    _when: activation => {
      return (
        activation.props.data.business_type &&
        CIN_BusinessTypes.indexOf(
          Number(activation.props.data.business_type)
        ) !== -1
      );
    },
  },
  {
    label: 'LLPIN',
    name: 'company_cin',
    required: true, // It's mandatory only for LLP
    info: 'Mandatory for LLP business. Example : AAB-1111',
    _when: activation =>
      activation.props.data.business_type &&
      LLPIN_BusinessTypes.indexOf(
        Number(activation.props.data.business_type)
      ) !== -1,
  },
  [
    {
      label: 'Company PAN Details',
      name: 'company_pan',
      placeholder: 'PAN Number',
      info:
        'Mandatory for Companies. PAN details should be of the mentioned business only.',
      validator: validatePANCard,
    },
    {
      label: 'PAN Owner Name',
      name: 'company_pan_name',
    },
  ],
  [
    {
      label: 'PAN info of Authorized Signatory/Promoter/Director',
      name: 'promoter_pan',
      placeholder: 'PAN Number',
      validator: validatePANCard,
      className: 'Input-vTop',
    },
    {
      label: 'PAN Owner Name',
      name: 'promoter_pan_name',
    },
  ],
  [
    {
      name: 'business_registered_address',
      placeholder: 'Enter Street Address',
      label: 'Registered Address',
      _cmp: Input.Textarea,
    },
    {
      name: 'business_registered_pin',
      label: 'Pincode',
      size: 'small',
      validator: value => {
        let pin = Number(value);
        if (!pin || pin < 100000 || pin > 999999) {
          return 'Please enter 6 digit pincode';
        }
      },
    },
    {
      name: 'business_registered_city',
      label: 'City',
    },
    {
      name: 'business_registered_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
    },
  ],
  {
    _name: 'same_address',
    label: 'Operational Address same as Registered Address',
    description: 'Physical verification may be performed at this address',
    _cmp: Input.Check,
  },
  [
    {
      name: 'business_operation_address',
      placeholder: 'Enter Street Address',
      label: 'Operational Address',
      _cmp: Input.Textarea,
      _when: differentAddress,
    },
    {
      name: 'business_operation_pin',
      type: 'number',
      label: 'Pincode',
      _when: differentAddress,
    },
    {
      name: 'business_operation_city',
      label: 'City',
      _when: differentAddress,
    },
    {
      name: 'business_operation_state',
      label: 'State',
      _when: differentAddress,
      _cmp: Input.Select,
      options: stateOptions,
    },
  ],
  [
    {
      _name: 'has_gstin',
      label: 'GSTIN',
      options: ['We have a registered GSTIN', "We don't have a GSTIN"],
      className: 'Input-vTop',
      _cmp: Input.Radio,
    },
    {
      name: 'gstin',
      _when: activation => activation.state.has_gstin === '0',
      placeholder: 'Enter GSTIN',
      size: 'small',
      info:
        'The entered GST Number should match your Operational Address. Example: 29AAGCR4375J1ZU',
      validator: value => {
        if (!isValidGSTIN(value)) {
          return 'Please provite valid GSTIN';
        }
      },
    },
  ],
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
    info: 'Your company account to which your payments will be settled.',
    autoComplete: 'new-password',
    type: 'password',
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
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    description: (
      <ul>
        Upload scan of the following:
        <li>
          Sales Tax/Service Tax or Shop Act Registration or GST Certificate
          (mandatory, if Proprietorship firm)
        </li>
        <li>Partnership Deed (mandatory, if Partnership firm)</li>
        <li>
          Certificate of Incorporation (mandatory, if Private Limited or LLP)
        </li>
        <li>Registration Proof or Certificate (Trust/Society/NGO etc.)</li>
      </ul>
    ),
  },
  {
    name: 'business_pan_url',
    label: 'Business PAN',
    description: 'The PAN details should match the ones provided earlier',
  },
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address",
    description:
      'Your Bank account number, IFSC code, and Company Name should be clearly visible',
  },
  {
    name: 'promoter_address_url',
    label: "Authorized Signatory's Address Proof",
    description:
      'Upload both sides of the government issued photo ID (Passport/Aadhaar/Driving License/Election Card)',
  },
  {
    name: 'form_12a_url',
    label: 'Form 12A Allotment Letter',
    _when: activation =>
      activation.props.data.business_type &&
      FORM_BusinessTypes.indexOf(
        Number(activation.props.data.business_type)
      ) !== -1,
  },
  {
    name: 'form_80g_url',
    label: 'Form 80G Allotment Letter',
    _when: activation =>
      activation.props.data.business_type &&
      FORM_BusinessTypes.indexOf(
        Number(activation.props.data.business_type)
      ) !== -1,
  },
];

// Tabs name
export const mainFormTabs = [
  'Contact Info',
  'Business Model',
  'Registration Details',
  'Bank Account',
  'Documents Upload',
];

// Tabs content
export default [
  contactFields,
  businessModel,
  registrationDetails,
  bankAccountFields,
  uploadFields,
];
