import React, { useState, type Dispatch, useContext } from 'react';
import { bladeTheme, ThemeTokens } from '@razorpay/blade/tokens';
import { BladeProvider } from '@razorpay/blade/components';

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
  return (
    <BladeThemeContext.Provider
      value={{
        theme,
        setTheme,
      }}
    >
      <BladeProvider themeTokens={themeMap[theme]}>{children}</BladeProvider>
    </BladeThemeContext.Provider>
  );
};
