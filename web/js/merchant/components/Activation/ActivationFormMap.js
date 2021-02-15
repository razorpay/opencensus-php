import Input from 'common/new-ui/Input';
import { states } from 'merchant/helpers/data';
import { WarningSvg } from 'merchant/components/Home/GenericPanel';
import { isValidGSTIN, getDetailsForIFSC, isPresent } from 'common/utils/rzp-utils';
import {
  validateCIN,
  validateIFSC,
  validatePANCard,
  validatePersonalPAN,
  validateCompanyPAN,
  validateCompanyAB,
  isUrlLenient,
  isValidName,
} from 'common/utils/validators';
import { trackLinkClick } from 'merchant/containers/Activation/ga_new';

import AddressFields from 'merchant/containers/Activation/AddressFieldsMap';

import {
  excludeFor_Indiv,
  isUnregisteredBusiness,
  _showForIndiv,
  isL1Completed,
  displayCompanyPAN,
  requiredForNGO,
  showForOrgs,
  isPANVerified,
  checkValidityFromAPI,
  getPANDescription,
  getBeneficiaryInfo,
  getBillingLabelInfo,
  getBusinessTypeInfo,
  getAccountNumberInfo,
  getBusinessNameInfo,
  hasSelectedBlacklistedCategory,
  doesHaveAdditionalDocs,
  getAdditionalDocCount,
  isAdditonalDocRequired,
  showSubcategory,
  isSourceRX,
  removeArrayDuplicatesByProp,
  hasUploadedBusinessProofUrl,
  hasUploadedBusinessProofTypeDoc,
  isBusinessProofTypeDocFieldVisible,
  canShowEAadharComponent,
} from './ActivationUtils';

import { ADDITIONAL_DOCS_LABEL_VALUE_MAP, BUSINESS_PROOF_TYPE_DOCS } from './Constants';

const PROPRIETORSHIP = 1;
const PARTNERSHIP = 3;
const PRIVATE = 4; // 'Private Limited',
const PUBLIC = 5; // 'Public Limited',
const LLP = 6; // 'LLP'
const NGO = 7; // 'NGO'
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'
const NOT_REGISTERED = 11; // 'Unregistered Businesses
const ADDRESS_PROOF_TYPES = {
  aadhar: {
    value: 'aadhar',
    label: 'Aadhaar',
    front: true,
    back: true,
    frontView: 'Front',
    backView: 'Back',
  },
  passport: {
    value: 'passport',
    label: 'Passport',
    front: true,
    back: true,
    frontView: 'First Page',
    backView: 'Last Page',
  },
  voter_id: {
    value: 'voter_id',
    label: 'Voter Id',
    front: true,
    back: true,
    frontView: 'Front',
    backView: 'Back',
  },
  // driver_license: {
  //   value: 'driver_license',
  //   label: "Driver's License",
  //   front: true,
  //   back: false,
  //   frontView: 'Front',
  //   backView: 'Back',
  // },
};
const commonDescription =
  'Ensure Signatory Name, IFSC code and Bank a/c is visible on the uploaded document.';
const BANK_PROOF_TYPE_DOC = {
  cancelled_cheque: {
    label: 'Canceled Cheque Copy',
    value: 'cancelled_cheque',
    description: commonDescription,
  },
  bank_statement: {
    label: 'Bank Statement Copy',
    value: 'bank_statement',
    description: commonDescription,
  },
};

export const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
export const E_SIGN_AADHAR = [PROPRIETORSHIP, PARTNERSHIP, NOT_REGISTERED];
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

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

const RegisteredBusinessTypeOptions = [
  { label: '--Select--', name: '' },
  { label: 'Private Limited', name: PRIVATE },
  { label: 'Proprietorship', name: PROPRIETORSHIP },
  { label: 'Partnership', name: PARTNERSHIP },
  { label: 'Public Limited', name: PUBLIC },
  { label: 'LLP', name: LLP },
  { label: 'Trust', name: TRUST },
  { label: 'Society', name: SOCIETY },
  { label: 'NGO', name: NGO },
];

const UnregisteredBusinessTypeOptions = [
  { label: '--Select--', name: '' },
  { label: 'Not Registered', name: NOT_REGISTERED },
];

var DefaultBusinessTypeOptions = removeArrayDuplicatesByProp(
  [...RegisteredBusinessTypeOptions, ...UnregisteredBusinessTypeOptions],
  'label',
);

const BlacklistedErr = () => (
  <div class="warning-svg red">
    {WarningSvg()}
    <span>We do not have the support for your business category selected as of now.</span>
  </div>
);
const businessModel = [
  {
    label: 'Business Type',
    name: 'business_type',
    _cmp: Input.Select,
    options: [], // options will be filled dynamically based on current activation stage
    info: getBusinessTypeInfo,
  },
  [
    {
      label: 'Business Category',
      name: 'business_category',
      _cmp: Input.Select,
      _autoRenderImpure: true,
      options: [],
      _disabledWhen: function (activation) {
        if (isSourceRX()) {
          const { activated, activation_flow } = activation.props.user;
          return activated || !!activation_flow;
        }
        return false;
      },
      description: (activation) => {
        if (!showSubcategory(activation) && hasSelectedBlacklistedCategory(activation)) {
          return <BlacklistedErr />;
        }
        return '';
      },
    },
    {
      label: 'Sub Category',
      name: 'business_subcategory',
      _cmp: Input.Select,
      _autoRenderImpure: true,
      options: [],
      _optionsFn: function (activation, categories) {
        // For setting options dynamically on basis some condition or other field selection
        const userSelection =
          activation.state.dirty.business_category || activation.props.data.business_category;

        if (userSelection && categories[userSelection]) {
          const subCategories = categories[userSelection].subcategories;

          this.options = ['--Select--'].concat(
            Object.keys(subCategories).map((c) => {
              const label = subCategories[c];

              return {
                name: c,
                label: typeof label === 'string' ? label : label.description,
              };
            }),
          );
        }

        return this.options;
      },
      description: (activation) => {
        if (hasSelectedBlacklistedCategory(activation)) {
          return <BlacklistedErr />;
        }
        return '';
      },
      _when: showSubcategory,
      _disabledWhen: (activation) => {
        if (isSourceRX()) {
          const { activated, activation_flow } = activation.props.user;
          return activated || !!activation_flow;
        }
        return false;
      },
    },
    {
      label: 'Business Description',
      name: 'business_model',
      _cmp: Input.Textarea,
      _autoRenderImpure: true,
      description:
        'Please give a brief description of the nature of your business. Please include examples of products you sell, the business category you operate under, your customers and the channels you primarily use to conduct your business(Website, offline retail etc).',
      placeholder: 'Minimum 200 characters',
      descriptionClass: 'Input--business-description',
      showCharacterLength: (val) => {
        if (val && val.length) {
          return val.length;
        }
      },
      _when: (activation) => {
        let { state, props } = activation;

        let businessCategory =
          state.dirty.business_category != null
            ? state.dirty.business_category
            : props.data.business_category;

        if (
          businessCategory === 'others' &&
          (isSourceRX() || !props.user.isBDAndAovEnabled || !props.user.isOrgRZP)
        ) {
          // If businessCategory is selected to others, then Business Model is to be filled in case of RX.
          return true;
        }

        return !isSourceRX() && props.user.isBDAndAovEnabled && props.user.isOrgRZP;
      },
    },
  ],
  {
    label: 'Average Order Value',
    _cmp: Input.Select,
    name: 'merchant_avg_order_value',
    extraChildren: (
      <div className="Input--aov-heading">
        Any payment recieved by my business would usually lie in range
      </div>
    ),
    _autoRenderImpure: true,
    options: [],
    _optionsFn: (activation) => {
      if (activation.props.aovRange.config) {
        return ['--Select--'].concat(
          activation.props.aovRange.config.map((item) => {
            if (item.max === 0) {
              return {
                name: 'More than 1,00,000₹',
                label: 'More than 1,00,000₹',
              };
            }
            return {
              name: `${item.min}-${item.max}₹`,
              label: `${item.min}-${item.max}₹`,
            };
          }),
        );
      }
    },
    _when: (activation) =>
      !isSourceRX() && activation.props.user.isBDAndAovEnabled && activation.props.user.isOrgRZP,
  },
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
                You can accept payments by sending out Payment Links and Invoices from Dashboard.
              </li>
              <li style={{ fontWeight: 'bold' }}>You will not get access to live APIs.</li>
              <li>You can upgrade anytime later by adding your website/app.</li>
            </ul>
          ),
        },
      ],
      // _disabledWhen: (activation) =>
      //   isL1Completed(activation) && isPresent(activation.props.data.business_website),
    },
    {
      label: '',
      name: 'business_website',
      placeholder: 'Enter URL',
      type: 'url',
      className: 'Input--Website-Url',
      validator: (value) => {
        if (!isUrlLenient(value)) {
          return 'Please enter a valid url';
        }
      },
      description: (
        <React.Fragment>
          The entered App/Website should contain:
          <div className="bullet-list-container">
            <ul className="bullet-list bullet-list--left">
              <li class="shallow"> About Us</li>
              <li class="shallow"> Contact Us</li>
              <li class="shallow"> Pricing</li>
            </ul>
            <ul className="bullet-list bullet-list--right">
              <li>
                <a
                  href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                  target="_blank"
                >
                  Privacy Policy
                </a>
              </li>
              <li>
                <a
                  href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                  target="_blank"
                >
                  Terms & Conditions
                </a>
              </li>
              <li>
                <a
                  href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                  target="_blank"
                >
                  Cancellation/Refund Policy
                </a>
              </li>
            </ul>
          </div>
        </React.Fragment>
      ),
      info: 'Payments will be enabled for the website/App after KYC approval.',
      _when: (activation) => activation.state.has_url === '0',
      // _disabledWhen: (activation) =>
      //   isL1Completed(activation) && isPresent(activation.props.data.business_website),
    },
  ],
];

const businessDetails = [
  [
    {
      label: 'Business PAN',
      name: 'company_pan',
      placeholder: 'PAN of the company',
      className: 'Input--capitalize',
      info: 'Mandatory for Companies. PAN details should be of the mentioned business only.',
      validator: validateCompanyPAN,
      checkValidityFromAPI: (activation) => {
        const errMsg =
          'The number entered doesn’t exist in the PAN database. Please verify and enter again';
        return checkValidityFromAPI(
          activation.props.data,
          'company_pan_verification_status',
          'incorrect_details',
          errMsg,
        );
      },
      _when: (activation) => displayCompanyPAN(activation),
    },
    {
      label: 'Business Name',
      name: 'business_name',
      options: [],
      info: getBusinessNameInfo,
      placeholder: 'Business name as per PAN',
      validator: function (value) {
        const contactName = this.state.dirty.contact_name || this.props.data.contact_name;
        const showCompanyName = this.props.user.isCompanyNameHiddenRazorX;
        return isUnregisteredBusiness(this)
          ? false
          : validateCompanyAB(value, contactName, showCompanyName);
      },
      _when: excludeFor_Indiv,
      // _disabledWhen: (activation) =>
      //   !isSourceRX() &&
      //   isL1Completed(activation) &&
      //   isPresent(activation.props.data.business_name),
      optionLabelPath: 'company_name',
      searchIndices: ['company_name'],
      className: 'ps-in-modal',
      customField: (activation) => {
        if (isSourceRX()) {
          return false;
        }
        const currentBusinessType =
          activation.state.dirty.business_type || activation.props.data.business_type;
        if (
          CIN_BusinessTypes.includes(Number(currentBusinessType)) ||
          LLPIN_BusinessTypes.includes(Number(currentBusinessType))
        ) {
          return true;
        }
        return false;
      },
    },
    {
      label: 'CIN',
      name: 'company_cin',
      validator: validateCIN,
      required: true, // It's mandatory only for certain orgs
      _autoRenderImpure: true,
      maxLength: '21',
      className: 'Input--capitalize',
      info: 'Example : U67190TN2014PTC096978',
      _when: (activation) => {
        const currentBusinessType =
          activation.state.dirty.business_type || activation.props.data.business_type;
        return currentBusinessType && CIN_BusinessTypes.indexOf(Number(currentBusinessType)) !== -1;
      },
    },
    {
      label: 'LLPIN',
      name: 'company_cin',
      required: true, // It's mandatory only for LLP
      info: 'Example : AAB-2933',
      className: 'Input--capitalize',
      validator: (value) => validateCIN(value, 'LLPIN'),
      _when: (activation) =>
        activation.props.data.business_type &&
        LLPIN_BusinessTypes.indexOf(Number(activation.props.data.business_type)) !== -1,
    },
  ],
  [
    {
      name: 'promoter_pan',
      placeholder: 'PAN Number',
      className: 'Input--capitalize',
      getPlaceholder: (activation) =>
        isUnregisteredBusiness(activation) ? 'Business owner’s PAN' : 'PAN of one of the directors',
      validator: function (value) {
        const isUnregBusiness = isUnregisteredBusiness(this);
        return validatePersonalPAN(value, isUnregBusiness);
      },
      getLabel: (activation) =>
        isUnregisteredBusiness(activation) ? 'PAN' : 'Authorised Signatory PAN',
      checkValidityFromAPI: (activation) => {
        const errMsg =
          'The number entered doesn’t exist in the PAN database. Please verify and enter again';
        return checkValidityFromAPI(
          activation.props.data,
          'poi_verification_status',
          'incorrect_details',
          errMsg,
        );
      },
      _disabledWhen: isPANVerified,
    },
    {
      getLabel: (activation) => {
        return isUnregisteredBusiness(activation) ? 'PAN Holder’s Name' : 'PAN Owner’s Name';
      },
      name: 'promoter_pan_name',
      placeholder: 'Name as per PAN',
      info: function () {
        return isUnregisteredBusiness(this) || !this.props.user.isRegAutoKYCEnabled
          ? ''
          : 'We verify the details with the central PAN database. Please ensure you enter the correct PAN details';
      },
      description: (activation) =>
        isUnregisteredBusiness(activation) ? getPANDescription(activation.props.data) : '',
      validator: (value) => {
        if (!isValidName(value)) {
          return 'PAN Name should not have any numbers or special characters.';
        }
      },
      checkValidityFromAPI: (activation) => {
        if (!isUnregisteredBusiness(activation)) {
          return null;
        }
        const errMsg = 'Please ensure you are entering the same spelling as on your PAN card';
        return checkValidityFromAPI(
          activation.props.data,
          'poi_verification_status',
          'not_matched',
          errMsg,
        );
      },
      _disabledWhen: isPANVerified,
    },
  ],
  {
    label: 'Billing Label',
    name: 'business_dba',
    _autoRenderImpure: true,
    required: true,
    info: getBillingLabelInfo,
    validator: (val) => {
      if (val && val.length < 3) {
        return 'Please enter billing label with at least 3 characters.';
      }
    },
  },
  ...AddressFields, // check ./AddressFieldsMap.js for address fields
  [
    {
      _name: 'has_gstin',
      label: 'GSTIN',
      options: ['We have a registered GSTIN', "We don't have a GSTIN"],
      className: 'Input--vTop Input--capitalize',
      _cmp: Input.Radio,
      _when: (activation) => excludeFor_Indiv(activation) && isL1Completed(activation),
      description: (activation) => {
        if (activation.state.has_gstin == '1') {
          return 'You can add your GST details later once you are registered';
        }
      },
      onChange: (e) => {
        if (e.target.value == '0') {
          // setTimeout to skip render cycle when GSTIN is being rendered in DOM
          setTimeout(() => document.getElementsByName('gstin')[0].focus(), 10); // Auto-select input box
        }
      },
    },
    {
      name: 'gstin',
      _when: (activation) => {
        return (
          excludeFor_Indiv(activation) &&
          activation.state.has_gstin === '0' &&
          isL1Completed(activation)
        );
      },
      _autoRenderImpure: true, // Re-render to show the error
      getLabel: (activation) => {
        return activation.isNeedsClarificationMode() && activation.isOnKYCTab() && 'GSTIN';
      },
      placeholder: 'Enter GSTIN',
      size: 'small',
      info: 'The entered GST Number should match either of the Address given above.',
      validator: (value) => {
        if (!isValidGSTIN(value)) {
          return 'Please provide valid GSTIN';
        }
      },
      checkValidityFromAPI: (activation) => {
        if (activation.state.has_gstin === '1') {
          return null;
        }
        return checkValidityFromAPI(
          activation.props.data,
          'gstin_verification_status',
          'incorrect_details',
          'Please provide the correct GSTIN details',
        );
      },
    },
  ],
];

const bankAccountFields = [
  {
    name: 'bank_account_name',
    label: 'Beneficiary Name',
    info: getBeneficiaryInfo,
    maxLength: '120',
    minLength: '4',
    validator: (val) => {
      if (val && !/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]{3,119}$/.test(val)) {
        return 'Name should contain at least 4 characters. Exclude numbers and special characters';
      }
    },
    description: (activation) =>
      isUnregisteredBusiness(activation) || activation.props.user.isRegAutoKYCEnabled
        ? 'We will deposit a small amount of money in your account to verify the account.'
        : '',
    linkedfields: ['bank_proof', 'bank_proof_doc'],
  },
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
    linkedfields: ['bank_proof', 'bank_proof_doc'],
  },
  [
    {
      name: 'bank_account_number',
      label: 'Account Number',
      info: getAccountNumberInfo,
      autoComplete: 'new-password',
      onBlur: function (e) {
        const bankAccountNumber = this.state.dirty.bank_account_number;
        const accountNo = this.state.account_no;

        const isMatching = bankAccountNumber == accountNo;

        if (!!bankAccountNumber && (!accountNo || !isMatching)) {
          document.querySelector('[data-name="account_no"]').focus(); // Focus on dependent field on Blur. Will be ignored if that is disabled.
        }
      },
      linkedfields: ['bank_proof', 'bank_proof_doc'],
    },
    {
      _name: 'account_no',
      label: 'Re-Enter Account Number',
      required: false,
      autoComplete: 'new-password',
      info: 'Please re-enter the bank account number.',
      _autoRenderImpure: true, // Re-render to show the error
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
];

const uploadFields = [
  {
    name: 'e_aadhar',
    customField: canShowEAadharComponent,
    _when: canShowEAadharComponent,
    isFieldValid: (activation) => {
      if (
        activation.props.data.stakeholder &&
        (activation.props.data.stakeholder.aadhaar_linked === 0 ||
          activation.props.data.stakeholder.aadhaar_linked === false)
      ) {
        return true;
      }
      if (
        activation.props.data.stakeholder &&
        activation.props.data.stakeholder.aadhaar_esign_status
      ) {
        return true;
      }
      return false;
    },
  },
  {
    label: 'Address Proof',
    getLabel: (activation) => {
      if (isUnregisteredBusiness(activation)) {
        return 'Address Proof';
      }
      return "Authorized Signatory's Address Proof";
    },
    className: (activation) => {
      if (!isUnregisteredBusiness(activation)) {
        return 'Input--vTop';
      }
      return null;
    },
    _name: 'address_proof',
    _cmp: Input.Select,
    options: Object.keys(ADDRESS_PROOF_TYPES).map((type) => {
      return { label: ADDRESS_PROOF_TYPES[type].label, name: type };
    }),
    _when: (activation) => {
      return _showForIndiv(activation) || activation.props.user.isRegAutoKYCEnabled;
    },
  },
  {
    label: 'First Page',
    name: 'address_proof_front',
    getLabel: (activation) => {
      const { address_proof } = activation.state;
      const addressProofType = ADDRESS_PROOF_TYPES[address_proof];
      return addressProofType.label + ' ' + addressProofType.frontView;
    },
    getName: (activation) => activation.state.address_proof + '_' + 'front',
    _cmp: Input.File,
    className: 'document-group',
    _type: 'address_proof_doc_upload',
    _when: (activation) => {
      return _showForIndiv(activation) || activation.props.user.isRegAutoKYCEnabled;
    },
  },
  {
    label: 'Last Page',
    name: 'address_proof_back',
    getLabel: (activation) => {
      const { address_proof } = activation.state;
      const addressProofType = ADDRESS_PROOF_TYPES[address_proof];
      return addressProofType.label + ' ' + addressProofType.backView;
    },
    getName: (activation) => activation.state.address_proof + '_' + 'back',
    _cmp: Input.File,
    className: 'document-group',
    _type: 'address_proof_doc_upload',
    _when: (activation) => {
      return _showForIndiv(activation) || activation.props.user.isRegAutoKYCEnabled;
    },
  },
  {
    name: 'business_proof_url',
    label: 'Business Registration Proof',
    _autoRenderImpure: true, // Here, Description on other field while render.
    _cmp: Input.File,
    description: (activation) => {
      const currentBusinessType =
        activation.state.dirty.business_type != null
          ? activation.state.dirty.business_type
          : activation.props.data.business_type;

      const li1 = 'GST Certificate / Shop Establishment Act Certificate / Registration Certificate';
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
    _when: (activation) => {
      const currentBusinessType =
        Number(activation.state.dirty.business_type) || Number(activation.props.data.business_type);
      if (!isUnregisteredBusiness(activation)) {
        if (currentBusinessType === PROPRIETORSHIP && hasUploadedBusinessProofTypeDoc(activation)) {
          return false;
        }
        if (
          currentBusinessType !== PROPRIETORSHIP ||
          (hasUploadedBusinessProofUrl(activation) && activation.props.data.submitted)
        ) {
          return true;
        }
      }
      return false;
    },
  },
  {
    label: 'Business Registration Proof',
    _name: 'business_proof_type',
    _cmp: Input.Select,
    options: Object.keys(BUSINESS_PROOF_TYPE_DOCS).map((type) => ({
      label: BUSINESS_PROOF_TYPE_DOCS[type],
      name: type,
    })),
    _when: isBusinessProofTypeDocFieldVisible,
    className: 'Input--vTop',
  },
  {
    name: 'shop_establishment_number',
    placeholder: 'As mentioned in the certificate',
    label: 'Shop Establishment Number',
    required: false,
    _autoRenderImpure: true,
    info: () =>
      'Your Shop establishment number is required to proceed with KYC. This will help us expedite the review of your KYC.',
    _when: (activation) => {
      return (
        activation.props.data.shop_establishment_verifiable_zone &&
        isBusinessProofTypeDocFieldVisible(activation) &&
        activation.state.business_proof_type === 'shop_establishment_certificate'
      );
    },
    className: 'Input--required Input--vTop document-group',
  },
  {
    label: '',
    name: 'business_proof_type_doc',
    getLabel: (activation) => BUSINESS_PROOF_TYPE_DOCS[activation.state.business_proof_type],
    getName: (activation) => activation.state.business_proof_type,
    _cmp: Input.File,
    description: (activation) => {
      const businessProofType = activation.state.business_proof_type;
      return `Upload the scan of ${BUSINESS_PROOF_TYPE_DOCS[businessProofType]}`;
    },
    _when: isBusinessProofTypeDocFieldVisible,
    className: 'document-group',
  },
  {
    name: 'business_pan_url',
    label: 'Company PAN',
    _cmp: Input.File,
    description: 'PAN details should be of the mentioned business only.',
    _when: (activation) => {
      const currentBusinessType =
        activation.state.dirty.business_type || activation.props.data.business_type;
      return !isUnregisteredBusiness(activation) && Number(currentBusinessType) !== PROPRIETORSHIP;
    },
  },
  {
    name: 'personal_pan',
    label: 'Personal PAN',
    _cmp: Input.File,
    description: 'Upload scanned copy of personal PAN Card',
    _when: (activation) => {
      const currentBusinessType =
        activation.state.dirty.business_type || activation.props.data.business_type;
      return !isUnregisteredBusiness(activation) && Number(currentBusinessType) === PROPRIETORSHIP;
    },
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
    name: 'address_proof_url',
    label: 'Bank Account Proof',
    _cmp: Input.File,
    description: (
      <>
        Please ensure your <b>Name, Account Number & Branch IFSC</b> are clearly visible on the
        document{' '}
      </>
    ),
    _when: (activation) => {
      /* when we ramp up the experiement, merchants who didn't fall 
        under the experiment shouldn't face any issue under needs clarfication flow */
      return (
        excludeFor_Indiv(activation) &&
        (!activation.props.user.isRegAutoKYCEnabled ||
          (activation.isNeedsClarificationMode() && activation.isOnKYCTab()))
      );
    },
  },
  {
    name: 'promoter_address_url',
    label: "Authorized Signatory's Address Proof",
    _cmp: Input.File,
    description: (
      <span>
        Upload<b> both sides </b>of the government issued photo ID (Passport / Driving License /
        Election Card). You can use{' '}
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
    _when: (activation) => {
      /* when we ramp up the experiement, merchants who didn't fall 
        under the experiment shouldn't face any issue under needs clarfication flow */
      return (
        excludeFor_Indiv(activation) &&
        (!activation.props.user.isRegAutoKYCEnabled ||
          (activation.isNeedsClarificationMode() && activation.isOnKYCTab()))
      );
    },
  },
  {
    label: 'Additional DOC',
    _name: 'additional_doc',
    _cmp: Input.Select,
    options: [],
    _when: (activation) =>
      doesHaveAdditionalDocs(activation) &&
      getAdditionalDocCount(activation.state, activation.props) > 1,
    required: (activation) => isAdditonalDocRequired(activation.state, activation.props),
  },
  {
    getLabel: (activation) => {
      const additionalDocKey =
        activation.state.dirty.additional_doc || activation.state.additional_doc;

      const userSelectedCategory =
        activation.state.dirty.business_category || activation.props.data.business_category;
      const userSelectedSubcategory =
        activation.state.dirty.business_subcategory || activation.props.data.business_subcategory;

      const additionalDocMapKey = `${userSelectedCategory}-${userSelectedSubcategory}`;
      const additionalDoc = ADDITIONAL_DOCS_LABEL_VALUE_MAP[additionalDocMapKey][additionalDocKey];

      return additionalDoc.label;
    },
    getName: (activation) => activation.state.additional_doc,
    _cmp: Input.File,
    className: 'document-group',
    _when: doesHaveAdditionalDocs,
    required: (activation) => isAdditonalDocRequired(activation.state, activation.props),
  },
];

export const ndcFields = [
  {
    label: 'Cancelled Cheque/Bank Account statement',
    _name: 'bank_proof',
    _cmp: Input.Select,
    options: Object.keys(BANK_PROOF_TYPE_DOC).map((type) => {
      return { label: BANK_PROOF_TYPE_DOC[type].label, name: type };
    }),
    _when: (activation) => {
      return activation.isNeedsClarificationMode() && activation.isOnKYCTab(); //Some improvements are possible here regarding placement of this field
    },
  },
  {
    label: '',
    name: 'bank_proof_doc',
    getLabel: (activation) => {
      const { bank_proof } = activation.state;
      return BANK_PROOF_TYPE_DOC[bank_proof].label;
    },
    getName: (activation) => activation.state.bank_proof,
    _type: 'address_proof_doc_upload',
    _autoRenderImpure: true,
    description: (activation) => {
      const { bank_proof } = activation.state;
      return BANK_PROOF_TYPE_DOC[bank_proof].description;
    },
    _cmp: Input.File,
    className: 'document-group',
    _when: (activation) => {
      return activation.isNeedsClarificationMode() && activation.isOnKYCTab(); //Some improvements are possible here regarding placement of this field
    },
    isNotDeletable: true,
    linkedfields: ['bank_proof'],
  },
];

export const bankAccountTabName = 'Bank Account';

// Tabs name
export const mainFormTabs = [
  'Contact Info',
  'Business Overview',
  'Business Details',
  bankAccountTabName,
  'Documents Upload',
];

export const tabToEventNames = [
  'contact_info',
  'business_overview',
  'business_details',
  'bank_account_details',
  'documents_upload',
  'needs_clarification',
];

// Tabs content
const tabsData = [contactFields, businessModel, businessDetails, bankAccountFields, uploadFields];

/* Handles not allowing changing Biz Type cross Reg -> Unreg / Unreg -> Reg after L1 Completion */
export const getBusinessTypeOptions = (activation) => {
  return DefaultBusinessTypeOptions;
};

/*
 * Note: If some Form Tab is removed from `tabsData`, then it's corresponding fields must also be removed from formNamesMeta.
 * The same you can check for data.need_kyc LA accounts
 */
export const mainFormFieldNamesMeta = (function () {
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
