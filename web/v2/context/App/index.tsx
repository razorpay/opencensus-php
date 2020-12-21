import React, { createContext, ReactNode, useEffect, useState } from 'react';
import { axiosInstance } from '../../services/graphql/graphql-fetch';
import { restInstance } from '../../services/rest/rest-fetch';

interface orgT {
  id: string;
}

export interface AppContextTypes {
  pathname?: string;
  query?: any;
  params?: any;
  user?: any;
  experiments?: any;
  org: orgT;
  mode: string;
  setMode?: (mode: string) => void;
}

const AppContext = createContext<AppContextTypes | undefined>(undefined);

function useApp(): AppContextTypes {
  const context = React.useContext(AppContext);
  if (!context) {
    throw new Error(`useApp must be used within a AppProvider`);
  }
  return context;
}

interface Props {
  context: AppContextTypes;
  children: ReactNode;
}

const AppProvider: React.FC<Props> = ({ context, children }) => {
  const [mode, setMode] = useState(context.mode);
  useEffect(() => {
    axiosInstance.defaults.headers.common['x-org-id'] = context.org.id;
    axiosInstance.defaults.headers.common['x-app-mode'] = mode;
    // restInstance.defaults.headers.common['X-Razorpay-Account'] = context.user.id;
    restInstance.defaults.baseURL = `${
      process.env.hostName ? process.env.hostName : ''
    }/merchant/api/${mode}`;
  }, [context, mode]);
  return (
    <AppContext.Provider value={{ ...context, mode, setMode }}>{children}</AppContext.Provider>
  );
};

export { AppProvider, useApp };

export default AppContext;
