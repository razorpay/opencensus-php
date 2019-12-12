import Input from 'common/new-ui/Input';

export default ({
  startsAt,
  getFormElementValidations,
  getFormOnChangeHandler,
  endsAt,
}) => (
  <React.Fragment>
    <Input.DateTime
      label="Starting On"
      name="starts_at"
      description="Start date for offer"
      onChange={getFormOnChangeHandler('datetime', 'starts_at')}
      isInline
      required
      validator={getFormElementValidations('starts_at')}
      defaultValue={startsAt}
    />
    <Input.DateTime
      label="Expires On"
      name="ends_at"
      onChange={getFormOnChangeHandler('datetime', 'ends_at')}
      description="Expiry date for offer"
      isInline
      required
      validator={getFormElementValidations('ends_at')}
      defaultValue={endsAt}
    />
  </React.Fragment>
);
