import Input from 'common/new-ui/Input';

import { WarningSvg } from 'merchant/components/Home/GenericPanel';

import { validatePersonalPAN, isValidWebsite } from 'common/utils/validators';

import AddressFields from './AddressFieldsMap';

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
const NOT_REGISTERED = 11;

const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

const individualMsg =
  'We are not supporting individuals (unregistered businesses) at the moment. We shall inform you when we start supporting individuals.';

export const BUSINESS_TYPE_OPTIONS = [
  { label: '--Select--', name: '' },
  { label: 'Private Limited', name: PRIVATE },
  { label: 'Proprietorship', name: PROPRIETORSHIP },
  { label: 'Partnership', name: PARTNERSHIP },
  { label: 'Individual', name: NOT_REGISTERED },
  { label: 'Public Limited', name: PUBLIC },
  { label: 'LLP', name: LLP },
  { label: 'Trust', name: TRUST },
  { label: 'Society', name: SOCIETY },
  { label: 'NGO', name: NGO },
];

/* Form fields of Payment Links */
export default [
  {
    label: 'Billing Label',
    name: 'business_dba',
    info:
      'The brand name that your customers are familiar with. It should either be similar to your registered business name or website name.',
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
      info: 'Please give a brief explanation of your business model and future plans',
      _cmp: Input.Textarea,
      _when: (activation) => {
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
      _when: (activation) => {
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
    label: 'Owner/Director/Propreitor PAN',
    name: 'promoter_pan',
    placeholder: 'PAN Number',
    className: 'Input--capitalize Input--vTop',
    validator: validatePersonalPAN,
    _when: excludeFor_Indiv,
    checkValidityFromAPI: (activation) => {
      if (activation.props.data.poi_verification_status === 'incorrect_details') {
        return 'The number entered doesn’t exist in the PAN database. Please verify and enter again';
      }
    },
  },
  {
    label: 'PAN Owner’s Name',
    name: 'promoter_pan_name',
    placeholder: 'Name as per PAN',
    info: function () {
      return !this.props.user.isRegAutoKYCEnabled
        ? ''
        : 'We verify the details with the central PAN database. Please ensure you enter the correct PAN details';
    },
  },
  ...AddressFields, // check ./AddressFieldsMap.js for address fields
  [
    {
      compressed: true,
    },
    {
      label: 'Business Type',
      name: 'business_type',
      _cmp: Input.Select,
      options: BUSINESS_TYPE_OPTIONS,
      description: (activation) => {
        // Changing description of self
        const currentBusinessType =
          activation.state.dirty.business_type || activation.props.data.business_type;

        // if user has selected individual business type
        if (currentBusinessType && !activation.props.accountId) {
          if (currentBusinessType == INDIVIDUAL) {
            return (
              <div class="warning-svg red">
                {WarningSvg()}
                <span>{individualMsg}</span>
              </div>
            );
          }
        }
      },
    },
    {
      label: 'Full Business Name',
      name: 'business_name',
      info: 'Example: Acme Infotech Private Limited',
    },
    {
      label: 'Website/App URL',
      _cmp: Input.Radio,
      _name: 'has_url',
      className: 'Input--vTop Input--Website',
      options: [
        'on my website/app',
        {
          label: 'without website/app',
          description: (
            <ul class="Input-desc-list">
              <li>
                You can accept payments by sending out Payment Links and Invoices from Dashboard.
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
      validator: (value) => {
        if (
          !isValidWebsite({ url: value, isRazorpayDomainAllowed: false, allowHttpProtocol: true })
        ) {
          return 'Invalid website URL';
        }
        return false;
      },
      info: 'Example: razorpay.com, play.google.com/?id=com.rzp',
      _when: (activation) => activation.state.has_url === '0',
    },
  ],
];

/* Return true IF NOT 'Individual/Not registered' business type */
function excludeFor_Indiv(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return [INDIVIDUAL, NOT_REGISTERED].indexOf(Number(currentBusinessType)) === -1;
}
