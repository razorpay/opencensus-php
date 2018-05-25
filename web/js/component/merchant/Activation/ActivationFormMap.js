import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

import { WarningSvg } from 'merchant/components/Home/GenericPanel';

import { getDetailsForIFSC } from 'common/util';
import { isValidGSTIN } from 'rzp/utils/rzp-utils';
import {
  validateCIN,
  validateIFSC,
  validatePANCard,
} from 'rzp/utils/validators';

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

const CIN_BusinessTypes = [PRIVATE, PUBLIC];
const LLPIN_BusinessTypes = [LLP];
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

const stateOptions = ['--Select--'].concat(
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
    info: 'We will reach out to this phone for any account related issues.',
  },
  {
    label: 'Contact Email',
    name: 'contact_email',
    type: 'email',
    info: 'We will reach out to this email for any account related issues.',
  },
];

const businessModel = [
  {
    label: 'Full Business Name',
    name: 'business_name',
    info: 'Example: Acme Infotech Private Limited',
  },
  {
    label: 'Billing Label',
    name: 'business_dba',
    info:
      'The brand name that your customers are familiar with. It should either be similar to your registered business name or website name.',
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
    description: function() {
      // Changing description of self
      const currentBusinessType =
        this.state.dirty.business_type || this.props.data.business_type;

      // if user has selected individual/not yet registered business type
      if (currentBusinessType && !this.props.accountId) {
        if (currentBusinessType == INDIVIDUAL) {
          return (
            <div class="warning-svg">
              {WarningSvg()}
              <span>
                Review of activation form for individuals takes longer. We may
                not be able to support a few business models at this moment.
              </span>
            </div>
          );
        } else if (currentBusinessType == NOT_REGISTERED) {
          return (
            <div class="warning-svg">
              {WarningSvg()}
              <span>
                Review of activation form for your case may take longer. We may
                not be able to support unregistered businesses at this moment.
              </span>
            </div>
          );
        }
      }
    },
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

          this.options = ['--Select--'].concat(
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
    label: () => <span>Want to accept international card payments</span>,
    name: 'business_international',
    _cmp: Input.Check,
    required: false,
    description:
      'Approval for international payments takes extra time to process. We will reach out to you as we may require some additional information.',
    _when: excludeFor_Indiv_NotReg,
  },
  {
    label: 'Link to Website/App',
    name: 'business_website',
    placeholder: 'Enter URL',
    type: 'url',
    description: (
      <React.Fragment>
        The entered App/Website should contain:
        <b class="shallow"> About Us</b>, <b class="shallow"> Contact</b>,{' '}
        <b class="shallow">
          <a
            href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
            target="_blank"
          >
            Privacy Policy
          </a>
        </b>,{' '}
        <b class="shallow">
          <a
            href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
            target="_blank"
          >
            Terms & Conditions
          </a>
        </b>,{' '}
        <b class="shallow">
          <a
            href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
            target="_blank"
          >
            Cancellation/Refund Policy
          </a>
        </b>{' '}
        & <b class="shallow">Pricing</b>. (Refer these links for sample pages)
      </React.Fragment>
    ),
    info: 'Example: razorpay.com, play.google.com/?id=com.rzp',
  },
];

const registrationDetails = [
  {
    label: 'CIN',
    name: 'company_cin',
    validator: validateCIN,
    required: true, // It's mandatory only for certain orgs
    maxLength: '21',
    info: 'Example : U67190TN014PTC096978',
    _when: activation => {
      const currentBusinessType =
        activation.state.dirty.business_type ||
        activation.props.data.business_type;

      return (
        currentBusinessType &&
        CIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1
      );
    },
  },
  {
    label: 'LLPIN',
    name: 'company_cin',
    required: true, // It's mandatory only for LLP
    info: 'Example : AAB-2933',
    _when: activation =>
      activation.props.data.business_type &&
      LLPIN_BusinessTypes.indexOf(
        Number(activation.props.data.business_type)
      ) !== -1,
  },
  {
    label: 'Company PAN Number',
    name: 'company_pan',
    placeholder: 'PAN Number',
    info:
      'Mandatory for Companies. PAN details should be of the mentioned business only.',
    validator: validatePANCard,
    _when: excludeFor_Indiv_NotReg,
  },
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
      required: false,
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
      maxLength: '6',
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
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
    {
      name: 'business_registered_state',
      label: 'State',
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
  ],
  {
    _name: 'same_address',
    label: 'Operational Address same as Registered Address',
    description: 'Physical Verification may take place at this address',
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
      label: 'Pincode',
      size: 'small',
      maxLength: '6',
      validator: value => {
        let pin = Number(value);
        if (!pin || pin < 100000 || pin > 999999) {
          return 'Please enter 6 digit pincode';
        }
      },
      _when: differentAddress,
    },
    {
      name: 'business_operation_city',
      label: 'City',
      _when: differentAddress,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
    {
      name: 'business_operation_state',
      label: 'State',
      _when: differentAddress,
      _cmp: Input.Select,
      options: stateOptions,
      _autoRenderImpure: true, // Re-evaluate errors if pincode is updated
    },
  ],
  [
    {
      _name: 'has_gstin',
      label: 'GSTIN',
      options: ['We have a registered GSTIN', "We don't have a GSTIN"],
      className: 'Input-vTop',
      _cmp: Input.Radio,
      _when: excludeFor_Indiv_NotReg,
      onChange: e => {
        if (e.target.value == '0') {
          // setTimeout to skip render cycle when GSTIN is being rendered in DOM
          setTimeout(() => document.getElementsByName('gstin')[0].focus(), 10); // Auto-select input box
        }
      },
    },
    {
      name: 'gstin',
      _when: activation => {
        return (
          excludeFor_Indiv_NotReg(activation) &&
          activation.state.has_gstin === '0'
        );
      },
      _autoRenderImpure: true, // Re-render to show the error
      placeholder: 'Enter GSTIN',
      size: 'small',
      required: false,
      info:
        'The entered GST Number should match either of the Address given above.',
      validator: value => {
        if (!isValidGSTIN(value)) {
          return 'Please provide valid GSTIN';
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
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    _autoRenderImpure: true, // Here, Description on other field while render.
    description: function() {
      const currentBusinessType =
        this.state.dirty.business_type != null
          ? this.state.dirty.business_type
          : this.props.data.business_type;

      const li1 =
        'GST Certificate / Shop Establishment Act Certificate / Registration Certificate';
      const li2 = 'Partnership Deed';
      const li3 = 'Certificate of Incorporation';
      const li4 = 'Registration Proof or Certificate';

      let description;

      if (currentBusinessType == PROPRIETORSHIP) {
        description = li1;
      } else if ([LLP, PARTNERSHIP].indexOf(Number(currentBusinessType)) > -1) {
        description = li2;
      } else if ([PRIVATE, PUBLIC].indexOf(Number(currentBusinessType)) > -1) {
        description = li3;
      } else if (ORG_BusinessTypes.indexOf(Number(currentBusinessType)) > -1) {
        description = li4;
      } else if (currentBusinessType == null) {
        description = (
          <ul>
            Upload scan as per your Business:
            <li>
              <b>Proprietorship firm: </b>
              {li1}
            </li>
            <li>
              <b>Partnership firm or LLP: </b>
              {li2}
            </li>
            <li>
              <b>Private Limited or Public: </b>
              {li3}
            </li>
            <li>
              <b>Trust, Society, NGO or others: </b>
              {li4}
            </li>
          </ul>
        );
      }

      if (typeof description === 'string') {
        description = 'Upload the scan of ' + description;
      }

      return description;
    },
    _when: excludeFor_Indiv_NotReg,
  },
  {
    name: 'business_pan_url',
    label: 'Company PAN',
    description: 'PAN details should be of the mentioned business only.',
    _when: excludeFor_Indiv_NotReg,
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
    description: (
      <span>
        Upload<b> both sides </b>of the government issued photo ID (Passport /
        Aadhaar / Driving License / Election Card). You can use{' '}
        <a href="http://www.pdfjoiner.com" target="_blank">
          pdfjoiner.com
        </a>{' '}
        to join 2 different photos.
      </span>
    ),
  },
  {
    name: 'form_12a_url',
    label: 'Form 12A Allotment Letter',
    _when: activation =>
      activation.props.data.business_type &&
      ORG_BusinessTypes.indexOf(Number(activation.props.data.business_type)) !==
        -1,
  },
  {
    name: 'form_80g_url',
    label: 'Form 80G Allotment Letter',
    _when: activation =>
      activation.props.data.business_type &&
      ORG_BusinessTypes.indexOf(Number(activation.props.data.business_type)) !==
        -1,
  },
];

/* Show fields if same_address is not ticked */
function differentAddress(activation) {
  return activation.state.same_address === '0';
}

/* Return true IF NOT 'Individual/Not registered' business type */
function excludeFor_Indiv_NotReg(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return (
    [NOT_REGISTERED, INDIVIDUAL].indexOf(Number(currentBusinessType)) === -1
  );
}

// Tabs name
export const mainFormTabs = [
  'Contact Info',
  'Business Overview',
  'Registration Details',
  'Bank Account',
  'Documents Upload',
];

// Tabs content
let tabsData;
export default (tabsData = [
  contactFields,
  businessModel,
  registrationDetails,
  bankAccountFields,
  uploadFields,
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
