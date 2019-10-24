import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

import { WarningSvg } from 'merchant/components/Home/GenericPanel';

import { getDetailsForIFSC } from 'common/util';
import { isValidGSTIN } from 'rzp/utils/rzp-utils';
import {
  validateCIN,
  validateIFSC,
  validatePANCard,
  isUrlLenient,
} from 'rzp/utils/validators';
import { trackLinkClick } from 'merchant/containers/Activation/ga_new';

import AddressFields from 'merchant/containers/Activation/AddressFieldsMap';

// This is as per the value saved in BE database
const PROPRIETORSHIP = 1;
export const INDIVIDUAL = 2;
const PARTNERSHIP = 3;
const PRIVATE = 4; // 'Private Limited',
const PUBLIC = 5; // 'Public Limited',
const LLP = 6; // 'LLP'
const NGO = 7; // 'NGO'
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'
const NOT_YET_REGISTERED = 11; // 'Unregistered Businesses
const AADHAR = 'aadhar';
const PASSPORT = 'passport';
const VOTER_ID = 'voter_id';
const DRIVER_LICENSE = 'driver_license';
const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};
const ADDRESS_PROOF_TYPES = {
  none: { value: null, label: '--Select--', front: false, back: false },
  aadhar: { value: 'aadhar', label: 'Aadhar', front: true, back: true },
  passport: { value: 'passport', label: 'Passport', front: true, back: true },
  voter_id: { value: 'voter_id', label: 'Voter Id', front: true, back: true },
  driver_license: {
    value: 'driver_license',
    label: "Driver's License",
    front: true,
    back: false,
  },
};
const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

const individualMsg =
  'We are not supporting individuals (unregistered businesses) at the moment. We shall inform you when we start supporting individuals.';

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
    _when: excludeFor_Indiv,
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
      { label: '--Select--', name: '' },
      { label: 'Private Limited', name: PRIVATE },
      { label: 'Proprietorship', name: PROPRIETORSHIP },
      { label: 'Partnership', name: PARTNERSHIP },
      // { label: 'Individual', name: INDIVIDUAL },
      { label: 'Public Limited', name: PUBLIC },
      { label: 'LLP', name: LLP },
      { label: 'Trust', name: TRUST },
      { label: 'Society', name: SOCIETY },
      { label: 'NGO', name: NGO },
      { label: 'Not Yet Registered', name: NOT_YET_REGISTERED },
    ],
    // description: activation => {
    //   // Changing description of self
    //   const currentBusinessType =
    //     activation.state.dirty.business_type ||
    //     activation.props.data.business_type;

    //   // if user has selected individual business type
    //   if (currentBusinessType && !activation.props.accountId) {
    //     if (currentBusinessType == INDIVIDUAL) {
    //       return (
    //         <div class="warning-svg red">
    //           {WarningSvg()}
    //           <span>{individualMsg}</span>
    //         </div>
    //       );
    //     }
    //   }
    // },
    _disabledWhen: activation => isL1Submitted(activation),
  },
  [
    {
      label: 'Business Category',
      name: 'business_category',
      _cmp: Input.Select,
      options: [],
      _disabledWhen: function(activation) {
        console.log(
          'activationPropsUserShowInstantActivation',
          activation.props.user.showInstantActivation
        );
        // return !!activation.props.user.showInstantActivation; TODO: Confirm with aseem about this condition
        return (
          activation.props.user.instantActivation.isL1Submitted &&
          !!activation.props.user.showInstantActivation
        );
      },
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
      _disabledWhen: function(form) {
        return !!form.props.user.showInstantActivation;
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
            Object.keys(subCategories).map(c => {
              const label = subCategories[c];

              return {
                name: c,
                label: typeof label === 'string' ? label : label.description,
              };
            })
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
      _disabledWhen: function(activation) {
        // return !!form.props.user.showInstantActivation; // TODO: change along with business_category
        return (
          activation.props.user.instantActivation.isL1Submitted &&
          !!activation.props.user.showInstantActivation
        );
      },
    },
  ],
  [
    {
      label: 'Website/App URL',
      _cmp: Input.Radio,
      _name: 'has_url',
      className: 'Input--vTop Input--Website',
      options: [
        'Website/App',
        {
          label: 'We do not have either',
          description: (
            <ul class="Input-desc-list">
              <li>
                You can accept payments by sending out Payment Links and
                Invoices from Dashboard.
              </li>
              <li>You will not get access to live APIs.</li>
              <li>You can upgrade anytime later by adding your website/app.</li>
            </ul>
          ),
        },
      ],
    },
    {
      label: '',
      name: 'business_website',
      placeholder: 'Enter URL',
      type: 'url',
      className: 'Input--Website-Url',
      validator: value => {
        if (!isUrlLenient(value)) {
          return 'Please enter a valid url';
        }
      },
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
      _when: activation => activation.state.has_url === '0',
    },
  ],
];

const registrationDetails = [
  {
    label: 'CIN',
    name: 'company_cin',
    validator: validateCIN,
    required: true, // It's mandatory only for certain orgs
    maxLength: '21',
    className: 'Input--capitalize',
    info: 'Example : U67190TN2014PTC096978',
    _when: activation => {
      const currentBusinessType =
        activation.state.dirty.business_type ||
        activation.props.data.business_type;
      return (
        isL1Submitted(activation) &&
        currentBusinessType &&
        CIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1
      );
    },
  },
  {
    label: 'LLPIN',
    name: 'company_cin',
    required: true, // It's mandatory only for LLP
    info: 'Example : AAB2933',
    className: 'Input--capitalize',
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
    className: 'Input--capitalize',
    info:
      'Mandatory for Companies. PAN details should be of the mentioned business only.',
    validator: validatePANCard,
    _when: activation =>
      isL1Submitted(activation) && excludeFor_CompanyPan(activation),
  },
  [
    {
      // label: 'PAN info of Authorized Signatory/Promoter/Director',
      // altLabel: 'PAN',
      dynamicLabel: true,
      name: 'promoter_pan',
      placeholder: 'PAN Number',
      validator: validatePANCard,
      getLabel: activation => {
        return UNREGISTERED_TYPES[
          Number(activation.state.dirty['business_type'])
        ] || UNREGISTERED_TYPES[Number(activation.props.data['business_type'])]
          ? 'PAN'
          : 'PAN info of Authorized Signatory/Promoter/Director';
      },
      className: 'Input--vTop Input--capitalize',
      _disabledWhen: isActivatedIndividual,
    },
    {
      // label: 'PAN Owner Name',
      dynamicLabel: true,
      getLabel: activation => {
        return UNREGISTERED_TYPES[
          Number(activation.state.dirty['business_type'])
        ] || UNREGISTERED_TYPES[Number(activation.props.data['business_type'])]
          ? 'PAN Holder’s Name'
          : 'PAN Owner Name';
      },
      name: 'promoter_pan_name',
      description: () => {
        return 'We verify the details with the central PAN database. Please ensure you enter the correct details';
      },
      _disabledWhen: isActivatedIndividual,
    },
  ],
  ...AddressFields, // check ./AddressFieldsMap.js for address fields
  [
    {
      _name: 'has_gstin',
      label: 'GSTIN',
      options: ['We have a registered GSTIN', "We don't have a GSTIN"],
      className: 'Input--vTop Input--capitalize',
      _cmp: Input.Radio,
      _when: activation =>
        excludeFor_Indiv(activation) && isL1Submitted(activation),
      description: activation => {
        if (activation.state.has_gstin == '1') {
          return 'You can add your GST details later once you are registered';
        }
      },
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
          excludeFor_Indiv(activation) &&
          activation.state.has_gstin === '0' &&
          isL1Submitted(activation)
        );
      },
      _autoRenderImpure: true, // Re-render to show the error
      placeholder: 'Enter GSTIN',
      size: 'small',
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
      onPaste: function(e) {
        e.preventDefault();
      }, // Disable copy-paste in this field
      onFocus: e => {
        document.getElementsByName('bank_account_number')[0].type = 'text';
      },
      onBlur: function(e) {
        document.getElementsByName('bank_account_number')[0].type = 'password';

        const bankAccountNumber = this.state.dirty.bank_account_number;
        const accountNo = this.state.account_no;

        const isMatching = bankAccountNumber == accountNo;

        if (!!bankAccountNumber && (!accountNo || !isMatching)) {
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
        if (!value) {
          return;
        }
        const bankAccountNo = this.state.dirty.bank_account_number;

        if (bankAccountNo && value !== bankAccountNo) {
          // Something changed in main 'bank account' field
          return 'Account no. does not match';
        }
      },
      _when: activation => {
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
    info: function() {
      const currentBusinessType =
        this.state.dirty.business_type || this.props.data.business_type;

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
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    _autoRenderImpure: true, // Here, Description on other field while render.
    _cmp: Input.File,
    description: activation => {
      const currentBusinessType =
        activation.state.dirty.business_type != null
          ? activation.state.dirty.business_type
          : activation.props.data.business_type;

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
    _when: excludeFor_Indiv,
  },
  {
    name: 'business_pan_url',
    label: 'Company PAN',
    _cmp: Input.File,
    description: 'PAN details should be of the mentioned business only.',
    _when: excludeFor_Indiv,
  },
  {
    name: 'address_proof_url',
    label: "Company's Bank Account Statement with Address",
    _cmp: Input.File,
    description:
      'Your Bank account number, IFSC code, and Company Name should be clearly visible',
    _when: excludeFor_Indiv,
  },
  {
    name: 'promoter_address_url',
    label: "Authorized Signatory's Address Proof",
    _cmp: Input.File,
    description: (
      <span>
        Upload<b> both sides </b>of the government issued photo ID (Passport /
        Driving License / Election Card). You can use{' '}
        <a
          href="http://www.pdfjoiner.com"
          target="_blank"
          onClick={() => trackLinkClick('pdfjoiner.com')}
        >
          pdfjoiner.com
        </a>{' '}
        to join 2 different photos.
      </span>
    ),
    _when: excludeFor_Indiv,
  },
  {
    name: 'form_12a_url',
    label: 'Form 12A Allotment Letter',
    _cmp: Input.File,
    required: requiredForNGO,
    _when: showForOrgs,
  },
  {
    name: 'form_80g_url',
    label: 'Form 80G Allotment Letter',
    _cmp: Input.File,
    required: requiredForNGO,
    _when: showForOrgs,
  },
  {
    label: 'Address Proof',
    _name: 'address_proof',
    _cmp: Input.Select,
    options: Object.keys(ADDRESS_PROOF_TYPES).map(type => {
      return { label: ADDRESS_PROOF_TYPES[type].label, name: type };
    }),
    _when: _showForIndiv,
  },
  {
    label: 'First Page',
    name: 'address_proof_front',
    dynamicLabel: true,
    dynamicName: true,
    getLabel: activation => {
      return (
        ADDRESS_PROOF_TYPES[activation.state.address_proof].label +
        ' ' +
        'First Page'
      );
    },
    getName: activation => {
      return activation.state.address_proof + '_' + 'front';
    },
    _cmp: Input.File,
    destinationUrl: 'merchant/documents/upload',
    // _when: activation => {
    //   return _showForIndiv(activation) && null !== activation.state.address_proof && _showForIndiv;
    // },
    _when: _showForIndiv,
    _type: 'address_proof_upload_doc',
    isDeletable: true,
  },
  {
    label: 'Last Page',
    name: 'address_proof_back',
    dynamicLabel: true,
    dynamicName: true,
    getLabel: activation => {
      // if (!activation.state.address_proof)
      //   return ADDRESS_PROOF_TYPES.aadhar.label + ' ' + 'Last Page';
      return (
        ADDRESS_PROOF_TYPES[activation.state.address_proof].label +
        ' ' +
        'Last Page'
      );
    },
    getName: activation => {
      // if (!activation.state.address_proof)
      //   return ADDRESS_PROOF_TYPES.aadhar.value + '_' + 'back';
      return activation.state.address_proof + '_' + 'back';
    },
    _cmp: Input.File,
    destinationUrl: 'merchant/documents/upload',
    // _when: activation => {
    //   return (
    //     _showForIndiv(activation) && null !== activation.state.address_proof &&
    //     ADDRESS_PROOF_TYPES[activation.state.address_proof].back
    //   );
    // },
    _when: _showForIndiv,
    _type: 'address_proof_upload_doc',
    isDeletable: true,
  },
];

/* Show fields if same_address is not ticked */
function differentAddress(activation) {
  return activation.state.same_address === '0';
}

/* Return true IF NOT 'Individual/Not registered' business type */
export function excludeFor_Indiv(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return (
    [INDIVIDUAL, NOT_YET_REGISTERED].indexOf(Number(currentBusinessType)) === -1
  );
}

/* Include for Individual/Not Yet Registered */
export function _showForIndiv(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return (
    [INDIVIDUAL, NOT_YET_REGISTERED].indexOf(Number(currentBusinessType)) !== -1
  );
}

function isL1Submitted(activation) {
  return activation.props.user.instantActivation.isL1Submitted;
}

function excludeFor_CompanyPan(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;
  return (
    [INDIVIDUAL, NOT_YET_REGISTERED, PROPRIETORSHIP].indexOf(
      Number(currentBusinessType)
    ) === -1
  );
}

function requiredForNGO(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return selectedBusinessType == NGO;
}

function showForOrgs(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return (
    selectedBusinessType &&
    ORG_BusinessTypes.indexOf(Number(selectedBusinessType)) !== -1
  );
}

function isActivatedIndividual(activation) {
  return !excludeFor_Indiv(activation) && activation.props.user.activated;
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
const tabsData = [
  contactFields,
  businessModel,
  registrationDetails,
  bankAccountFields,
  uploadFields,
];

/*
* Note: If some Form Tab is removed from `tabsData`, then it's corresponding fields must also be removed from formNamesMeta.
* The same you can check for data.need_kyc LA accounts
*/
export const mainFormFieldNamesMeta = (function() {
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

export default tabsData;
