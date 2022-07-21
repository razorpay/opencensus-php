import FormProvider from './FormContext';
import FormWrapper from './FormWrapper';

const ApmOnboarding = (props) => {
  return (
    <FormProvider>
      <FormWrapper {...props} />
    </FormProvider>
  );
};

export default ApmOnboarding;
