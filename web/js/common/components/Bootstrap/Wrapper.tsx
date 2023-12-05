import React, { ReactNode } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
  MutationFunction,
} from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Provider } from 'react-redux';
import { ThemeProvider } from 'styled-components';
import { LayerProvider } from 'common/components/Layer/LayerContext';
import { SnackbarProvider } from 'common/components/SnackBar/SnackbarContext';
import { AppProvider, AppContextTypes } from 'common/context/App';
import {
  graphqlRequestQuery,
  graphqlRequestMutation,
} from 'common/services/graphql/graphql-client';
import store from 'merchant/store';

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

const Wrapper: React.FC<Props> = ({ context, children }) => {
  return (
    <Provider store={store}>
      <BladeProvider themeTokens={paymentTheme}>
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
