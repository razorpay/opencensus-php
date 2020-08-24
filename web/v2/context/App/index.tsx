import React, { createContext, ReactNode } from 'react';

export type AppContextTypes = {
  pathname?: string;
  query?: unknown;
  params?: unknown;
  user?: unknown;
  experiments?: unknown;
};

const AppContext = createContext<AppContextTypes>({
  pathname: '',
  query: {},
  params: {},
  user: {},
  experiments: {},
});

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
  return <AppContext.Provider value={context}>{children}</AppContext.Provider>;
};

export { AppProvider, useApp };

export default AppContext;
