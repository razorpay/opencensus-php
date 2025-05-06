import React, { useState } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { I18nProvider } from '@razorpay/i18nify-react';
import { ThemeProvider } from 'styled-components';
import ConnectedProfileDropdownWrapper from './ConnectedProfileDropdownWrapper';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';

const TopNavActions = () => {
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <I18nProvider>
        <ThemeProvider theme={theme}>
          <ConnectedProfileDropdownWrapper />
        </ThemeProvider>
      </I18nProvider>
    </BladeProvider>
  );
};
export default TopNavActions;
