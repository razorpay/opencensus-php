import React, { ReactNode } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';
import { SnackbarProvider } from 'v2/components/SnackBar/SnackbarContext';
import { AppProvider, AppContextTypes } from '../../context/App';
import { LayerProvider } from '../Layer/LayerContext';
import { fetchGraphQL } from '../../services/graphql/graphql-fetch';

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
    <ThemeProvider theme={theme}>
      <ReactQueryCacheProvider queryCache={queryCache}>
        <AppProvider context={context}>
          <LayerProvider>
            <SnackbarProvider>{children}</SnackbarProvider>
          </LayerProvider>
        </AppProvider>
      </ReactQueryCacheProvider>
    </ThemeProvider>
  );
};

export default Wrapper;
