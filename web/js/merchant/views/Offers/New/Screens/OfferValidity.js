import React from 'react';
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
import { getEOD } from '../helpers';
import moment from 'moment';
const LINK_TO_DOCS = 'https://razorpay.com/docs/payment-gateway/orders/';
const PAYMENT_FAILURE_OPTIONS = [
  { label: '--Select Type--', name: '' },
  { label: 'Do not allow payment to go through', name: '1' },
  { label: 'Allow customer to pay without availing offer', name: '0' },
];

// TODO: FIX: ends_at default value should be null fix it
// eslint-disable-next-line no-undef
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
  handleEndDate = (value) => {
    this.handleFormChange('ends_at', value);
  };

  handleStartDate = (value) => {
    const {
      values: { ends_at },
    } = this.props;
    if (!value) {
      this.handleFormChange('starts_at', undefined);
      return;
    }
    this.handleFormChange('starts_at', value);
  };

  componentDidMount() {
    const {
      values: { ends_at },
    } = this.props;
    if (!ends_at) {
      this.handleEndDate(moment(getEOD()));
    }
  }

  getStartAtProps() {
    const {
      values: { starts_at },
    } = this.props;
    const startAtProps = {};

    if (starts_at) {
      startAtProps.defaultValue = starts_at;
    }

    return startAtProps;
  }

  handleFormChange = (name, value) => {
    this.props.setFieldTouched(name);
    this.props.setFieldValue(name, value);
  };
  render() {
    const { isFormLocked, showSubscriptionOfferFields, values, errors, touched } = this.props;

    const defaultEndDate = values.ends_at || moment(getEOD());
    errors.block = validateBlock(values.block);
    errors.max_offer_usage = validateMaxOfferUsage(values.max_offer_usage);
    errors.ends_at = validateExpiry(values.starts_at, values.ends_at);

    const startAtProps = this.getStartAtProps();
    return (
      <div class="offers-duration-container">
        <Input.DateTime
          isInline
          label="Starting On"
          description="Start date for offer"
          checkboxFieldLabel="Starts Immediately"
          class="Input--vTop"
          onChange={this.handleStartDate}
          disabled={isFormLocked}
          {...startAtProps}
        />

        <Input.DateTime
          isInline
          required
          label="Expires On"
          defaultValue={defaultEndDate}
          description="Expiry date for offer"
          class="Input--vTop"
          onChange={this.handleEndDate}
          disabled={isFormLocked}
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

export function validateExpiry(startDate, endDate) {
  if (!startDate || endDate.unix() >= startDate.unix()) return false;
  return 'End date should be after start date.';
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
