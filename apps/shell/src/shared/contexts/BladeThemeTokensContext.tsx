import React, { useState, type Dispatch, useContext } from 'react';
import { bladeTheme, ThemeTokens } from '@razorpay/blade/tokens';
import { BladeProvider } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../../client/components/Navigation/hooks';
import '@razorpay/blade/fonts.css';

type BladeThemeType = 'blade';

const themeMap: { [key: string]: ThemeTokens } = {
  blade: bladeTheme,
};

const BladeThemeContext = React.createContext({
  theme: 'blade' as BladeThemeType,
  setTheme: (() => null) as Dispatch<string>,
});

export const useBladeTheme = () => useContext(BladeThemeContext);

export const BladeThemeProvider = ({ children }: { children: JSX.Element | JSX.Element[] }) => {
  const [theme, setTheme] = useState<BladeThemeType>('blade');
  const { isBankingActive } = useGetActiveProduct();

  const getInitialColorScheme = () => {
    // TODO: return 'dark' for banking when launching X in connected dashboard
    if (isBankingActive) {
      return 'light';
    } else {
      return 'light';
    }
  };

  return (
    <BladeThemeContext.Provider
      value={{
        theme,
        setTheme,
      }}
    >
      <BladeProvider themeTokens={themeMap[theme]} colorScheme={getInitialColorScheme()}>
        {children}
      </BladeProvider>
    </BladeThemeContext.Provider>
  );
};
