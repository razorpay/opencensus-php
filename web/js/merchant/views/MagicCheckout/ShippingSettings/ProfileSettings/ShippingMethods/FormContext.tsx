import React, { useEffect, useState } from 'react';
import { getDefaultFormValues } from './constants';
import { ShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { buildFormDataFromMethod } from './helpers';

export type Inputs =
  | 'name'
  | 'delivery_type'
  | 'description'
  | 'fee'
  | 'allow_cod'
  | 'estimated_delivery_details'
  | 'attribute_rules'
  | 'fee_rules'
  | 'etd';

export type FormInput = {
  value: any;
  validation: (val) => string;
  error: undefined | string;
};

interface FormContext {
  values: Record<Inputs, FormInput>;
  setValue: (key: Inputs, value: any) => void;
  selectedMethod: any;
  setSelectedMethod: (val: any) => void;
  resetForm: () => void;
}

const FormContext = React.createContext<FormContext | null>(null);

const FormContextProvider = ({ children }: { children: React.ReactNode }): JSX.Element => {
  const [values, setValues] = useState<Record<Inputs, FormInput>>({ ...getDefaultFormValues() });
  const [selectedMethod, setSelectedMethod] = useState<ShippingMethod | null>(null);

  useEffect(() => {
    if (selectedMethod?.id) {
      const values = buildFormDataFromMethod(selectedMethod);
      setValues(values);
    } else {
      setValues({ ...getDefaultFormValues() });
    }
  }, [selectedMethod?.name]);

  const setFormValue = (key: Inputs, value: unknown) => {
    const errorMessage = values[key].validation(value);
    values[key] = {
      ...values[key],
      value,
      error: errorMessage,
    };
    setValues({ ...values });
  };

  const resetForm = () => {
    setValues({ ...getDefaultFormValues() });
    setSelectedMethod(null);
  };

  const setSelectedMethodHandler = (value) => {
    setSelectedMethod(value);
  };

  return (
    <FormContext.Provider
      value={{
        values,
        setValue: setFormValue,
        selectedMethod,
        setSelectedMethod: setSelectedMethodHandler,
        resetForm,
      }}
    >
      {children}
    </FormContext.Provider>
  );
};

const useFormContext = (): FormContext => {
  const ctx = React.useContext(FormContext);
  if (!ctx) {
    throw Error(
      'useFormContext cannot be used outside components wrapped with FormContextProvider',
    );
  }
  return ctx;
};

export { FormContextProvider, useFormContext };
