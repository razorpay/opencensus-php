import React, { ReactNode } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade/src/tokens/theme.web';
import ErrorBoundary from '../ErrorBoundary/ErrorBoundary';
import { AppProvider, AppContextTypes } from '../../context/App';
import { LayerProvider } from '../Layer/LayerContext';

interface Props {
  context: AppContextTypes;
  children: ReactNode;
}

const Wrapper: React.FC<Props> = ({ context, children }) => {
  return (
    <ThemeProvider theme={theme}>
      <AppProvider context={context}>
        <ErrorBoundary>
          <LayerProvider>{children}</LayerProvider>
        </ErrorBoundary>
      </AppProvider>
    </ThemeProvider>
  );
};

export default Wrapper;
