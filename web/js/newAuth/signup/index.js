import React from 'react';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import Signup from './signup';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import { LayerProvider } from 'common/components/Layer/LayerContext';
import { BrowserRouter as Router } from 'react-router-dom';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

const SignupWrap = () => {
  return (
    <Provider store={store}>
      <Router basename="/app">
        <BladeProvider themeTokens={paymentTheme} colorScheme="light">
          <LayerProvider>
            <Signup />
            <ModalDialog />
            <Notifications />
          </LayerProvider>
        </BladeProvider>
      </Router>
    </Provider>
  );
};

export default SignupWrap;
