import React from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade/src/tokens/theme.web';
import ErrorBoundary from '../ErrorBoundary/ErrorBoundary';
import Layout from '../../merchant/onboarding/mobile/Layout';
import { AppProvider, AppContextTypes } from '../../context/App';

interface Props {
  context: AppContextTypes;
}

const Wrapper: React.FC<Props> = ({ context }) => {
  return (
    <ThemeProvider theme={theme}>
      <AppProvider context={context}>
        <ErrorBoundary>
          <Layout />
        </ErrorBoundary>
      </AppProvider>
    </ThemeProvider>
  );
};

export default Wrapper;
