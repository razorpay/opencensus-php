import Input from 'common/new-ui/Input';
import { MAX_DISCOUNT } from 'merchant/views/Offers/constants';
import DocsLink from 'merchant/components/DocsLink';
import { rupeesToPaise } from 'common/utils/rzp-utils';

const LINK_TO_DOCS = 'https://razorpay.com/docs/payment-gateway/orders/';
const PAYMENT_FAILURE_OPTIONS = [
  { label: '--Select Type--', name: '' },
  { label: 'Do not allow payment to go through', name: 1 },
  { label: 'Allow customer to pay without availing offer', name: 0 },
];

// TODO: FIX: ends_at default value should be null fix it
export default class OfferValidity extends React.Component {
  handleDate = (name) => {
    return (value) => {
      this.props.onChange({
        target: {
          name,
          value,
        },
      });
    };
  };

  render() {
    const { formData, isFormLocked, showSubscriptionOfferFields } = this.props;

    return (
      <div class="offers-duration-container">
        <Input.DateTime
          isInline
          label="Starting On"
          description="Start date for offer"
          defaultValue={formData.starts_at}
          checkboxFieldLabel="Starts Immediately"
          class="Input--vTop"
          onChange={this.handleDate('starts_at')}
          disabled={isFormLocked}
        />

        <Input.DateTime
          isInline
          required
          label="Expires On"
          description="Expiry date for offer"
          class="Input--vTop"
          validator={validatesEndsAt(formData.start_at)}
          onChange={this.handleDate('ends_at')}
          disabled={isFormLocked}
          defaultValue={formData.ends_at}
        />

        <Input.Select
          required
          name="block"
          label="On Payment Failure"
          defaultValue={formData.block}
          description="What happens at times of failure of offer validation for customer?"
          options={PAYMENT_FAILURE_OPTIONS}
          validator={validateBlock}
          disabled={isFormLocked}
        />

        <Input
          type="number"
          name="max_offer_usage"
          label="Max Usage"
          placeholder="Max Usage of this offer: Example - 100 times"
          defaultValue={formData.max_offer_usage}
          validator={validateMaxOfferUsage}
          disabled={isFormLocked}
          description={
            showSubscriptionOfferFields && 'How many subscription will be able to use this offer.'
          }
        />

        <Input.Check
          name="default_offer"
          label="Show Offer on Checkout"
          className="Input--vTop"
          fieldLabel="Offer will be available for all customers on checkout."
          // field has been renamed to allow gradual deprecation towards default_offer
          defaultValue={formData.default_offer}
          disabled={isFormLocked}
        />

        <p className="offers-api-note">
          If you are not using Woocommerce, Magento or Shopify plugin, you will need to integrate
          the Orders API for this feature to work. Learn more about
          <DocsLink url={LINK_TO_DOCS} title="Orders API here" />
        </p>
      </div>
    );
  }
}

export function validatesEndsAt(start_at) {
  return (val) => {
    if (!val) return 'Please select a date';

    if (start_at >= val) {
      return 'End date cannot be less that start date.';
    }
  };
}

export function validateBlock(val) {
  if (!val || val == '') {
    return 'Please select an option';
  }
}

export function validateMaxOfferUsage(val) {
  if (!val) return;

  if (!new RegExp('^[0-9]+$').test(val)) {
    return 'Please enter a number';
  }

  val = parseFloat(val);
  // Converting to value entered in RS to Paise for proper validation
  val = rupeesToPaise(val);
  if (val > MAX_DISCOUNT) {
    return `Maximum value allowed is ${MAX_DISCOUNT}`;
  }
}
