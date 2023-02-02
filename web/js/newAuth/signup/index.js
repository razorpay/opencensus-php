import React from 'react';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import Signup from './signup';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import { BrowserRouter as Router } from 'react-router-dom';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

const SignupWrap = () => {
  return (
    <Provider store={store}>
      <Router basename="/app">
        <BladeProvider themeTokens={paymentTheme} colorScheme="light">
          <Signup />
          <ModalDialog />
          <Notifications />
        </BladeProvider>
      </Router>
    </Provider>
  );
};

export default SignupWrap;
