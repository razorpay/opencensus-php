import React from 'react';

interface NavigationLayoutContext {
  isSideNavOpenOnMobile: boolean;
  setIsSideNavOpenOnMobile: React.Dispatch<React.SetStateAction<boolean>>;
  onSwitchMode: (args) => void;
  isConnectedNavigation?: boolean;
}

export const NavigationLayoutContext = React.createContext({} as NavigationLayoutContext);

export const useNavigationLayoutContext = () => React.useContext(NavigationLayoutContext);
