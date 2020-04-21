import Input from 'common/new-ui/Input';

export default ({
  name,
  getFormOnChangeHandler,
  getFormElementValidations,
  displayText,
  terms,
  type,
  isOnlyNoCostEmi,
}) => {
  return (
    <React.Fragment>
      <Input
        label="Offer Name"
        name="name"
        placeholder="Example: New Year Sale (This name appears on your dashboard)"
        autoFocus={true}
        defaultValue={name}
        required
        validator={getFormElementValidations('name')}
        onChange={getFormOnChangeHandler()}
      />
      <Input
        label="Display Text"
        name="display_text"
        placeholder="10% off on all HDFC Debit Cards (This appears on checkout for your customers)"
        required
        defaultValue={displayText}
        validator={getFormElementValidations('display_text')}
        onChange={getFormOnChangeHandler()}
      />
      <Input.Textarea
        label="Terms"
        name="terms"
        placeholder="Terms and conditions for offer"
        defaultValue={terms}
        validator={getFormElementValidations('terms')}
        description={'Enter offer terms and conditions'}
        required
        onChange={getFormOnChangeHandler()}
      />
      {!isOnlyNoCostEmi && (
        <Input.Select
          name="type"
          label="Offer Type"
          required
          defaultValue={type}
          options={[
            { label: 'Please select', name: '' },
            { label: 'Instant', name: 'instant' },
            { label: 'Cashback', name: 'deferred' },
            { label: 'Already Discounted', name: 'already_discounted' },
          ]}
          onChange={getFormOnChangeHandler('stateResetter')([
            'discount_type',
            'min_amount',
          ])}
          description={
            type === 'deferred' &&
            'Cashbacks need to be processed by the provider (Wallet providers, Banks etc). Please create Cashback Offers only if you have an agreement in place with them'
          }
        />
      )}
    </React.Fragment>
  );
};
