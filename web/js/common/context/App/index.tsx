import React, { createContext, ReactNode, useState } from 'react';
import { axiosInstance } from 'common/services/graphql/graphql-fetch';
import { restInstance } from 'common/services/rest/rest-fetch';
import { setMode as setGlobalMode, ModeT } from 'common/services/mode';

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
  mode: ModeT;
  setMode?: (mode: ModeT) => void;
  submerchantId?: string;
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
  const [mode, setMode] = useState<ModeT>(context?.mode);
  axiosInstance.defaults.headers.common['x-org-id'] = context?.org?.id;
  axiosInstance.defaults.headers.common['x-app-mode'] = mode;
  // restInstance.defaults.headers.common['X-Razorpay-Account'] = context.user.id;
  restInstance.defaults.baseURL = `${process.env.hostName ? process.env.hostName : ''}`;
  setGlobalMode(mode);

  return (
    <AppContext.Provider value={{ ...context, mode, setMode }}>{children}</AppContext.Provider>
  );
};

export { AppProvider, useApp };

export default AppContext;
