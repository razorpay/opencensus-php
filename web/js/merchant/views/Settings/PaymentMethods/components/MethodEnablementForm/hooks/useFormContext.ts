import { useContext } from 'react';

import { formContext } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/components/FormContext';
import { FormContextType } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';

const useFormContext = (): FormContextType => {
  return useContext(formContext) as FormContextType;
};

export default useFormContext;
