import React, { createContext, ReactNode, useState } from 'react';
import { setMode as setGlobalMode } from 'common/services/mode';
import type { DASHBOARD_MODE } from '@libs/shared-types';

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
  mode: DASHBOARD_MODE;
  setMode?: (mode: DASHBOARD_MODE) => void;
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
  const [mode, setMode] = useState<DASHBOARD_MODE>(context?.mode);
  setGlobalMode(mode);

  return (
    <AppContext.Provider value={{ ...context, mode, setMode }}>{children}</AppContext.Provider>
  );
};

export { AppProvider, useApp };

export default AppContext;
