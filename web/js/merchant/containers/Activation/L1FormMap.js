import Input from 'component/Input';
import { states } from 'rzp/utils/constants';

import { WarningSvg } from 'merchant/components/Home/GenericPanel';

import { validatePANCard, isUrlLenient } from 'rzp/utils/validators';

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

const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

const individualMsg =
  'We are not supporting individuals (unregistered businesses) at the moment. We shall inform you when we start supporting individuals.';

/* Form fields of Payment Links */
export default [
  {
    label: 'Business Type',
    name: 'business_type',
    _cmp: Input.Select,
    options: [
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
    ],
    description: activation => {
      // Changing description of self
      const currentBusinessType =
        activation.state.dirty.business_type ||
        activation.props.data.business_type;

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
    },
  ],
  {
    label: 'Company PAN Number',
    name: 'company_pan',
    placeholder: 'PAN Number',
    className: 'Input--capitalize',
    info:
      'Mandatory for Companies. PAN details should be of the mentioned business only.',
    validator: validatePANCard,
    _when: excludeFor_Indiv,
  },
  [
    {
      label: 'Website/App URL',
      _cmp: Input.Radio,
      _name: 'has_url',
      className: 'Input--vTop',
      options: [
        'Website/App',
        {
          label: 'We do not have either',
        },
      ],
    },
    {
      label: '',
      name: 'business_website',
      placeholder: 'Enter URL',
      type: 'url',
      validator: value => {
        if (!isUrlLenient(value)) {
          return 'Please enter a valid url';
        }
      },
      info: 'Example: razorpay.com, play.google.com/?id=com.rzp',
      _when: activation => activation.state.has_url === '0',
    },
  ],
];

/* Return true IF NOT 'Individual/Not registered' business type */
function excludeFor_Indiv(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return [INDIVIDUAL].indexOf(Number(currentBusinessType)) === -1;
}
