// test-utils.js
import React, { ReactNode } from 'react';
import { render } from '@testing-library/react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade/src/tokens/theme.web';
import { LayerProvider } from '../../components/Layer/LayerContext';

const AllTheProviders: React.FC<{ children: ReactNode }> = ({ children }) => {
  return (
    <ThemeProvider theme={theme}>
      <LayerProvider>{children}</LayerProvider>
    </ThemeProvider>
  );
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const customRender = (ui, options) => render(ui, { wrapper: AllTheProviders, ...options });

// re-export everything
export * from '@testing-library/react';

// override render method
export { customRender as render };
