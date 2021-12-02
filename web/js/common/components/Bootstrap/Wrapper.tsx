import React, { ReactNode } from 'react';
import { Provider } from 'react-redux';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';
import { SnackbarProvider } from '../SnackBar/SnackbarContext';
import { AppProvider, AppContextTypes } from '../../context/App';
import { LayerProvider } from '../Layer/LayerContext';
import { fetchGraphQL } from '../../services/graphql/graphql-fetch';
import store from '../../../merchant/store';

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
      <ThemeProvider theme={theme}>
        <ReactQueryCacheProvider queryCache={queryCache}>
          <AppProvider context={context}>
            <LayerProvider>
              <SnackbarProvider>{children}</SnackbarProvider>
            </LayerProvider>
          </AppProvider>
        </ReactQueryCacheProvider>
      </ThemeProvider>
    </Provider>
  );
};

export default Wrapper;
