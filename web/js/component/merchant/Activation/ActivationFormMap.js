import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

import { getDetailsForIFSC } from 'common/util';
import { isValidGSTIN } from 'rzp/utils/rzp-utils';
import { validateCIN, validatePANCard } from 'rzp/utils/validators';

const NGO_BUSINESS_TYPE = 7;
const differentAddress = activation => activation.state.same_address === '0';

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
  [
    {
      label: 'Do you have Website/App?',
      _cmp: Input.Radio,
      _name: 'app_type',
      options: [
        'Yes',
        {
          label: "We don't have either",
          description: (
            <React.Fragment>
              You can still accept payments through <b>Razorpay Invoices</b> and
              <b> Razorpay Payment Links</b>. You can request access to other
              products (<b>Route</b>, <b>Subscription</b>, <b>Smart Collect</b>)
              once you have a website or app.
            </React.Fragment>
          ),
        },
      ],
    },
    {
      name: 'business_website',
      placeholder: 'Enter URL',
      type: 'url',
      required: false,
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
      _when: activation => activation.state.app_type !== '1',
      info: 'Example: https://www.company.com',
    },
  ],
];

const registrationDetails = [
  {
    label: 'CIN',
    name: 'company_cin',
    validator: validateCIN,
  },
  [
    {
      label: 'Company PAN Details',
      name: 'company_pan',
      placeholder: 'PAN Number',
      info: 'PAN details should belong to the business mentioned above',
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
      type: 'number',
      label: 'Pincode',
      size: 'small',
      min: '100000',
      max: '999999',
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
      _cmp: Input.Radio,
    },
    {
      name: 'gstin',
      _when: activation => activation.state.has_gstin === '0',
      placeholder: 'Enter GSTIN',
      size: 'small',
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
      activation.props.data.business_type == NGO_BUSINESS_TYPE,
  },
  {
    name: 'form_80g_url',
    label: 'Form 80G Allotment Letter',
    _when: activation =>
      activation.props.data.business_type == NGO_BUSINESS_TYPE,
  },
];

// Tabs name
export const mainFormTabs = [
  'Contact Details',
  'Business Model',
  'Registration Details',
  'Bank Account Details',
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
