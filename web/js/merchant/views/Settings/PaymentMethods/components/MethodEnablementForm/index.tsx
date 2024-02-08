import React from 'react';

import FormProvider from './components/FormContext';
import FormWrapper from './components/FormWrapper';
import { MethodEnablementFormProps } from './types';

const MethodEnablementForm = (props: MethodEnablementFormProps): React.ReactElement => {
  return (
    <FormProvider>
      <FormWrapper {...props} />
    </FormProvider>
  );
};

export default MethodEnablementForm;
