import FORM_TYPE from './Forms';

export default props => {
  const { formType, handleChange, user } = this.props;

  const currentForm = FORM_TYPE(user)[formType],
    WizardForm = currentForm.formComponent;

  return (
    <React.Fragment key="form">
      <WizardForm handleChange={handleChange} />
    </React.Fragment>
  );
};
