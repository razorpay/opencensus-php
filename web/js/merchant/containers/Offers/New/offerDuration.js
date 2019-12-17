import Input from 'common/new-ui/Input';

export default ({
  startsAt,
  block,
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
      defaultValue={endsAt}
      checkboxFieldLabel={'No Expiry'}
    />
    <Input.Select
      validator={getFormElementValidations('block')}
      label="Block Payment"
      name="block"
      defaultValue={block}
      description="What happens at times of failure of offer validation for customer?"
      required
      options={[
        { label: 'Select Type', name: '' },
        { label: 'Block', name: true },
        { label: 'Allow', name: false },
      ]}
    />
    <Input
      label="Max Usage"
      name="max_offer_usage"
      type="number"
      placeholder="Max Usage of this offer: Example - 100 times"
      required
      defaultValue={maxOfferUsage}
    />
  </React.Fragment>
);
