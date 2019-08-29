import FORM_TYPE from './Forms';

export default props => {
  const { formType, handleChange, handleFileUpload } = props;

  const currentForm = FORM_TYPE[formType],
    WizardForm = currentForm.formComponent;

  return (
    <React.Fragment key="form">
      <WizardForm
        handleChange={handleChange}
        handleFileUpload={handleFileUpload}
      />
    </React.Fragment>
  );
};
