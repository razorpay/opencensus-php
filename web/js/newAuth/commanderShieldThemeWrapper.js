import React from 'react';
import { ThemeProvider } from 'styled-components';
/**
 * We use the theme from a different blade-old release because commander-shield uses a different version of blade
 * This scopes out commander shield from any theming change that may bleed in from dashboard
 */
import { lightTheme as theme } from '@razorpay/blade-old-for-new-auth/src/tokens/theme';

// eslint-disable-next-line valid-jsdoc
/**
 * When we use any commander-shield module we wrap it so theme tokens can be correctly overridden
 * This is required to prevent theme collision because commander-shield uses a different blade-old version
 * This will prevent theme.bladeOld references from affecting commander-shield
 */
const CommanderShieldThemeWrapper = ({ children }) => (
  <ThemeProvider theme={theme}>{children}</ThemeProvider>
);

export default CommanderShieldThemeWrapper;
