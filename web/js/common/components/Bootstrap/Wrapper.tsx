import React, { ReactNode } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme, createTheme } from '@razorpay/blade/tokens';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
  MutationFunction,
} from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Provider } from 'react-redux';
import { createGlobalStyle, ThemeProvider } from 'styled-components';

import { LayerProvider } from 'common/components/Layer/LayerContext';
import { SnackbarProvider } from 'common/components/SnackBar/SnackbarContext';
import { AppProvider, AppContextTypes } from 'common/context/App';
import {
  graphqlRequestQuery,
  graphqlRequestMutation,
} from 'common/services/graphql/graphql-client';
import store from 'merchant/store';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: graphqlRequestQuery,
    },
    mutations: {
      mutationFn: graphqlRequestMutation as MutationFunction<unknown, unknown>,
    },
  },
});

interface Props {
  context: AppContextTypes;
  children: ReactNode;
}

const GlobalStyles = createGlobalStyle`
  body {
    font-family: ${(props) => props.theme.typography.fonts.family.text}
  }

  h1, h2, h3, h4, h5, h6 {
    font-family: ${(props) => props.theme.typography.fonts.family.heading};
  }

  th {
    font-weight: ${(props) => props.theme.typography.fonts.weight.semibold};
  }
`;

const Wrapper: React.FC<Props> = ({ context, children }) => {
  const splitz = useSplitzService();
  const isCustomTheme = isExperimentEnabled(splitz?.abExperiments?.enable_trxn_v2_parity_features);
  let customTheme = bladeTheme;

  if (isCustomTheme) {
    const color = window?.rzp_org?.merchant_styles?.primary;
    if (color) {
      const { theme: customColorTheme } = createTheme({ brandColor: color });
      customTheme = customColorTheme;
    }
  }

  return (
    <Provider store={store}>
      <BladeProvider themeTokens={customTheme}>
        <GlobalStyles />
        <ThemeProvider theme={theme}>
          <ReactQueryClientProvider client={queryClient}>
            <AppProvider context={context}>
              <LayerProvider>
                <SnackbarProvider>{children}</SnackbarProvider>
              </LayerProvider>
              {process.env.PUBLIC_ENV == 'development' ? (
                <ReactQueryDevtools initialIsOpen={false} />
              ) : null}
            </AppProvider>
          </ReactQueryClientProvider>
        </ThemeProvider>
      </BladeProvider>
    </Provider>
  );
};

export default Wrapper;
