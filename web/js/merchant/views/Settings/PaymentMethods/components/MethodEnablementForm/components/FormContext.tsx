import React, { createContext, useState } from 'react';

import { FORM_INITIAL_VALUES } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import {
  ApiDataType,
  FormContextType,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';

export const formContext = createContext<FormContextType | undefined>(undefined);

const FormProvider = ({ children }: { children: React.ReactNode }): JSX.Element => {
  const [selectedTab, setSelectedTab] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [alert, setAlert] = useState('');
  const [initialValues, setInitialValues] = useState(FORM_INITIAL_VALUES);
  const [apiData, setApiData] = useState<ApiDataType | null>(null);

  const onTabClick = (index: number) => {
    setSelectedTab(index);
  };

  const value: FormContextType = {
    selectedTab,
    onTabClick,
    initialValues,
    setInitialValues,
    isLoading,
    setIsLoading,
    apiData,
    setApiData,
    alert,
    setAlert,
  };

  return <formContext.Provider value={value}>{children}</formContext.Provider>;
};

export default FormProvider;
