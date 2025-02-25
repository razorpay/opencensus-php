import React from 'react';
import { BladeThemeProvider } from './BladeThemeTokensContext';
import { GlobalStyle } from '@apps/shell/src/shared/styled';

export const ShellCommonProvider = ({ children }) => {
  return (
    <BladeThemeProvider>
      <GlobalStyle />
      {children}
    </BladeThemeProvider>
  );
};
