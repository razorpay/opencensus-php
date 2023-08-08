import React, { ReactNode } from 'react';
import { Provider } from 'react-redux';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';
import { SnackbarProvider } from 'common/components/SnackBar/SnackbarContext';
import { AppProvider, AppContextTypes } from 'common/context/App';
import { LayerProvider } from 'common/components/Layer/LayerContext';
import { fetchGraphQL } from 'common/services/graphql/graphql-fetch';
import store from 'merchant/store';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

export const queryCache = new QueryCache({
  defaultConfig: {
    queries: {
      queryFn: fetchGraphQL,
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
          <ReactQueryCacheProvider queryCache={queryCache}>
            <AppProvider context={context}>
              <LayerProvider>
                <SnackbarProvider>{children}</SnackbarProvider>
              </LayerProvider>
            </AppProvider>
          </ReactQueryCacheProvider>
        </ThemeProvider>
      </BladeProvider>
    </Provider>
  );
};

export default Wrapper;
