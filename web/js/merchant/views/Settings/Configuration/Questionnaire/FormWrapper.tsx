import React, { useCallback, useEffect } from 'react';
import debounce from 'common/utils/debounce';
import { useFormikContext } from 'formik';

const FormWrapper = ({
  children,
  validateTab,
  activeTab,
  isRevampFlow,
}: {
  children: React.ReactNode;
  validateTab: (arg0: { values: any; errors: any }, arg1: false, arg2: number) => void;
  activeTab: number;
  isRevampFlow: boolean;
}) => {
  const { values, errors, validateForm } = useFormikContext();

  const validationCb = useCallback(
    (...rest) => {
      validateTab(...rest);
    },
    [validateTab],
  );

  useEffect(() => {
    // this is needed as for first time user, form is not being validated
    if (isRevampFlow) validateForm();
  }, [isRevampFlow]);

  const debouncedValidation = debounce(validationCb, 100);

  useEffect(() => {
    if (isRevampFlow) debouncedValidation({ values, errors }, false, activeTab);
  }, [values, errors, activeTab, isRevampFlow]);

  return children;
};

export default FormWrapper;
