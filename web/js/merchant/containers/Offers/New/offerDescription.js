import Input from 'common/new-ui/Input';

export default ({ name, getFormElementValidations, displayText, terms }) => {
  return (
    <React.Fragment>
      <Input
        label="Offer Name"
        name="name"
        placeholder="Offer Short name"
        autoFocus={true}
        defaultValue={name}
        required
        validator={getFormElementValidations('name')}
      />
      <Input
        label="Display Text"
        name="display_text"
        placeholder="Display text for offer"
        required
        defaultValue={displayText}
        validator={getFormElementValidations('display_text')}
      />
      <Input.Textarea
        label="Terms"
        name="terms"
        placeholder="Terms and conditions for offer"
        defaultValue={terms}
        validator={getFormElementValidations('terms')}
        required
      />
    </React.Fragment>
  );
};
