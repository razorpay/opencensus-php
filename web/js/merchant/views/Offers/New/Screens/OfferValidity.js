import {
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Checkbox,
  CheckboxGroup,
} from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';
import { rupeesToPaise } from 'common/utils/rzp-utils';
import DocsLink from 'merchant/components/DocsLink';
import { MAX_DISCOUNT } from 'merchant/views/Offers/constants';
const LINK_TO_DOCS = 'https://razorpay.com/docs/payment-gateway/orders/';
const PAYMENT_FAILURE_OPTIONS = [
  { label: '--Select Type--', name: '' },
  { label: 'Do not allow payment to go through', name: '1' },
  { label: 'Allow customer to pay without availing offer', name: '0' },
];

// TODO: FIX: ends_at default value should be null fix it
export default class OfferValidity extends React.Component {
  handleDate = (name, handleChange) => {
    return (value) => {
      handleChange({
        target: {
          name,
          value,
        },
      });
    };
  };

  handleFormChange = (name, value) => {
    this.props.setFieldTouched(name);
    this.props.setFieldValue(name, value);
  };
  render() {
    const {
      isFormLocked,
      showSubscriptionOfferFields,
      values,
      handleChange,
      handleBlur,

      errors,
      touched,
    } = this.props;

    errors.block = validateBlock(values.block);
    errors.max_offer_usage = validateMaxOfferUsage(values.max_offer_usage);
    return (
      <div class="offers-duration-container">
        <Input.DateTime
          isInline
          label="Starting On"
          description="Start date for offer"
          checkboxFieldLabel="Starts Immediately"
          class="Input--vTop"
          onChange={(value) => this.handleDate('starts_at', handleChange)(value)}
          disabled={isFormLocked}
          onBlur={handleBlur}
        />

        <Input.DateTime
          isInline
          required
          label="Expires On"
          description="Expiry date for offer"
          class="Input--vTop"
          validator={validatesEndsAt(values.start_at)}
          onChange={(value) => this.handleDate('ends_at', handleChange)(value)}
          disabled={isFormLocked}
          onBlur={handleBlur}
        />

        <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7" marginTop="spacing.7">
          <SelectInput
            isRequired
            necessityIndicator="required"
            label="On Payment Failure"
            placeholder="--Select Type--"
            name="block"
            labelPosition="left"
            helpText="What happens at times of failure of offer validation for customer?"
            value={values.block}
            onBlur={handleBlur}
            onChange={({ name, values }) => {
              this.handleFormChange(name, values[0]);
            }}
            validationState={touched.block && errors?.block ? 'error' : 'none'}
            errorText={errors?.block}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(PAYMENT_FAILURE_OPTIONS).map((type) => (
                <ActionListItem
                  key={type.name}
                  title={type.label}
                  value={type.name}
                  testID={`option-${type.name}`}
                />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>

        <TextInput
          type="number"
          label="Max Usage"
          labelPosition="left"
          name="max_offer_usage"
          placeholder="Max Usage of this offer: Example - 100 times"
          helpText={
            showSubscriptionOfferFields && 'How many subscription will be able to use this offer.'
          }
          validationState={touched.max_offer_usage && errors?.max_offer_usage ? 'error' : 'none'}
          errorText={errors?.max_offer_usage}
          isDisabled={isFormLocked}
          marginBottom="spacing.7"
          value={values.max_offer_usage}
          onChange={({ name, value }) => {
            this.handleFormChange(name, value);
          }}
          onBlur={handleBlur}
        />

        <CheckboxGroup
          label="Show Offer on Checkout"
          labelPosition="left"
          marginBottom="spacing.3"
          helpText={
            <p>
              If you are not using Woocommerce, Magento or Shopify plugin, you will need to
              integrate the Orders API for this feature to work. Learn more about
              <DocsLink url={LINK_TO_DOCS} title="Orders API here" />
            </p>
          }
          name="default_offer"
          value={values.default_offer}
          onChange={({ name, values }) => {
            this.handleFormChange(name, values?.[0]);
          }}
        >
          <Checkbox value="1">Offer will be available for all customers on checkout.</Checkbox>
        </CheckboxGroup>
      </div>
    );
  }
}

export function validatesEndsAt(start_at) {
  // eslint-disable-next-line consistent-return
  return (val) => {
    if (!val) return 'Please select a date';

    if (start_at >= val) {
      return 'End date cannot be less that start date.';
    }
  };
}

export function validateBlock(val) {
  if ((val === undefined || val == '') && val !== 0) {
    return 'Please select an option';
  }
  return false;
}

export function validateMaxOfferUsage(val) {
  if (!val || val === '') return false;

  if (!new RegExp('^[0-9]+$').test(val)) {
    // eslint-disable-next-line consistent-return
    return 'Please enter a number';
  }

  val = parseFloat(val);
  // Converting to value entered in RS to Paise for proper validation
  val = rupeesToPaise(val);
  if (val > MAX_DISCOUNT) {
    // eslint-disable-next-line consistent-return
    return `Maximum value allowed is ${MAX_DISCOUNT}`;
  }
  // eslint-disable-next-line consistent-return
  return false;
}
