import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Provider } from 'react-redux';
import { Router } from 'react-router-dom';

import { storeWithInitialState } from 'merchant/store';
import { OrderSessionContext } from 'merchant/views/GCMS/Orders/context';

import GCMSWrapper from './Wrapper';
import { SessionContext } from './context';

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

export const GCMSTestWrapperRenderer = ({ history, children }) => {
  const initialState = {
    session: {
      mode: 'test',
      merchantId: 'N91osUDdN9WdO9',
    },
  };

  return (
    <Provider store={storeWithInitialState(initialState)}>
      <Router location={history.location} navigator={history}>
        <GCMSWrapper>{children}</GCMSWrapper>
      </Router>
    </Provider>
  );
};

export const GCMSTestPageRenderer = ({ children }) => {
  const initialState = {
    session: {
      mode: 'test',
      merchantId: 'N91osUDdN9WdO9',
    },
  };

  return (
    <Provider store={storeWithInitialState(initialState)}>
      <BladeProvider themeTokens={paymentTheme}>
        <QueryClientProvider client={queryClient}>
          <SessionContext.Provider value={{ mode: 'test', merchantId: 'NDnRD3epJ6P60L' }}>
            <OrderSessionContext.Provider
              value={{
                orderId: 'NMmhaRfFheRmhA',
                resellerId: 'N91osUDdN9WdO9',
                setOrderId: jest.fn(),
              }}
            >
              {children}
            </OrderSessionContext.Provider>
          </SessionContext.Provider>
        </QueryClientProvider>
      </BladeProvider>
    </Provider>
  );
};
