import React from 'react';
import { Provider } from 'react-redux';
import { GoogleReCaptchaProvider as ReCaptchaV3Provider } from 'react-google-recaptcha-v3';
import store from 'merchant/store';
import Signup from './signup';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import { BrowserRouter as Router } from 'react-router-dom';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { LayerProvider } from 'common/components/Layer/LayerContext';

const SignupWrap = () => {
  return (
    <Provider store={store}>
      <Router basename="/app">
        <LayerProvider>
          <ReCaptchaV3Provider reCaptchaKey={process.env.RECAPTCHA_V3_SITE_KEY}>
            <BladeProvider themeTokens={paymentTheme} colorScheme="light">
              <Signup />
              <ModalDialog />
              <Notifications />
            </BladeProvider>
          </ReCaptchaV3Provider>
        </LayerProvider>
      </Router>
    </Provider>
  );
};

export default SignupWrap;
