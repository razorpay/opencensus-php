import Input from 'common/new-ui/Input';

const LINK_TO_DOCS = 'https://razorpay.com/docs/payment-gateway/orders/;';

export default ({
  startsAt,
  block,
  checkoutDisplay,
  getFormElementValidations,
  getFormOnChangeHandler,
  endsAt,
  maxOfferUsage,
}) => (
  <React.Fragment>
    <Input.DateTime
      label="Starting On"
      name="starts_at"
      description="Start date for offer"
      onChange={getFormOnChangeHandler('datetime', 'starts_at')}
      isInline
      validator={getFormElementValidations('starts_at')}
      defaultValue={startsAt}
      checkboxFieldLabel={'Starts Immediately'}
    />
    <Input.DateTime
      label="Expires On"
      name="ends_at"
      onChange={getFormOnChangeHandler('datetime', 'ends_at')}
      description="Expiry date for offer"
      isInline
      validator={getFormElementValidations('ends_at')}
      defaultValue={endsAt || ''}
      required
    />
    <Input.Select
      validator={getFormElementValidations('block')}
      label="On Payment Failure"
      name="block"
      defaultValue={block}
      description="What happens at times of failure of offer validation for customer?"
      required
      options={[
        { label: 'Select Type', name: '' },
        { label: 'Do not allow payment to go through', name: true },
        { label: 'Allow customer to pay without availing offer', name: false },
      ]}
      onChange={getFormOnChangeHandler()}
    />
    <Input
      label="Max Usage"
      name="max_offer_usage"
      type="number"
      validator={getFormElementValidations('max_offer_usage')}
      placeholder="Max Usage of this offer: Example - 100 times"
      defaultValue={maxOfferUsage}
      onChange={getFormOnChangeHandler()}
    />
    <Input.Check
      label={'Checkout Visibility'}
      onChange={getFormOnChangeHandler()}
      className={'Input--vTop'}
      fieldLabel={'Offer available for all users on checkout.'}
      //field has been renamed to allow gradual deprecation towards default_offer
      name="default_offer"
      defaultValue={checkoutDisplay}
    />
    <p className="offers-api-note">
      If you aren't using Woocommerce, Magento or Shopify plugin, you'll need to
      integrate the{' '}
      <a target="_blank" href={LINK_TO_DOCS}>
        Orders API
      </a>{' '}
      for this feature to work.
    </p>
  </React.Fragment>
);
