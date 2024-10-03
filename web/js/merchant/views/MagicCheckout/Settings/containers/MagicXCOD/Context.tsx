import React, { useCallback, useState, createContext, useContext, useEffect } from 'react';

import {
  initialFormContext,
  initialFormData,
  INVALID_PAYMENT_METHOD,
  INVALID_COD_CONFIG,
  INVALID_COD_RANGE,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';

import {
  FormErrors,
  FormData,
  FormContextType,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

const FormContext = createContext<FormContextType>(initialFormContext);

export const FormContextProvider = ({ children }: { children: React.ReactNode }): JSX.Element => {
  const [formData, setFormData] = useState<FormData>(initialFormData);
  const [formErrors, setFormErrors] = useState<FormErrors>({});

  const validateForm = useCallback((formData) => {
    let errObj: FormErrors = {};

    if (!formData?.allow_cod && !formData?.allow_prepaid)
      errObj.paymentMethod = INVALID_PAYMENT_METHOD;
    else {
      const { lt, gte } = formData?.cod_fee_rules?.amount ?? {};
      if (lt == null && gte == null) errObj = { ...errObj };
      else if (lt == null || gte == null) errObj.codSlabs = INVALID_COD_CONFIG;
      else if (Number(gte) >= Number(lt)) errObj.codSlabs = INVALID_COD_RANGE;
    }
    setFormErrors(errObj);
    return !Object.keys(errObj).length;
  }, []);

  useEffect(() => {
    validateForm(formData);
  }, [formData]);

  const updateFormData = useCallback((key, value) => {
    setFormData((formData) => {
      const updatedFormData = {
        ...formData,
        [key]: value,
      };
      validateForm(updatedFormData);
      return updatedFormData;
    });
  }, []);

  const resetForm = useCallback(() => {
    setFormErrors({});
    setFormData(initialFormData);
  }, []);

  const initialiseFormData = useCallback((form) => setFormData(form), []);

  return (
    <FormContext.Provider
      value={{
        formData,
        initialiseFormData,
        updateFormData,
        resetForm,
        formErrors,
      }}
    >
      {children}
    </FormContext.Provider>
  );
};

export const useFormContext = (): FormContextType => useContext(FormContext);
