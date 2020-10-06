import React, { createContext, ReactNode, useState } from 'react';
import { axiosInstance } from '../../services/graphql/graphql-fetch';

export type AppContextTypes = {
  pathname?: string;
  query?: unknown;
  params?: unknown;
  user?: unknown;
  experiments?: unknown;
  orgId: string;
  mode: string;
  setMode?: (mode: string) => void;
};

const AppContext = createContext<AppContextTypes | undefined>(undefined);

function useApp(): AppContextTypes | Error {
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
  axiosInstance.defaults.headers.common['x-org-id'] = context.orgId;
  axiosInstance.defaults.headers.common['x-app-mode'] = mode;
  return (
    <AppContext.Provider value={{ ...context, mode, setMode }}>{children}</AppContext.Provider>
  );
};

export { AppProvider, useApp };

export default AppContext;
