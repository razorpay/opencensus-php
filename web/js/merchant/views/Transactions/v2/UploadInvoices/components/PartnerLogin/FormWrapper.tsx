import React from 'react';
import { Formik } from 'formik';

import { FORM_VALIDATION } from './constants';
import { getInitialFormValues } from './utils';
import { FormWrapperProps } from './types';

const FormWrapper = ({ children, partner, user }: FormWrapperProps): JSX.Element => {
  const gstin = user?.gstin;
  const initialValues = getInitialFormValues(partner, gstin);

  return (
    <Formik
      initialValues={initialValues}
      validationSchema={FORM_VALIDATION[partner]}
      validateOnMount
      enableReinitialize
      onSubmit={() => {}}
    >
      {children}
    </Formik>
  );
};

export default FormWrapper;
