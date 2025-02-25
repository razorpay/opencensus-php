import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { GoogleReCaptchaProvider as ReCaptchaV3Provider } from 'react-google-recaptcha-v3';
import { Provider } from 'react-redux';
import { BrowserRouter as Router } from 'react-router-dom';

import { LayerProvider } from '@libs/web-nexus/common/components/Layer/LayerContext';
import ModalDialog from '@libs/web-nexus/common/ui/ModalDialog';
import Notifications from '@libs/web-nexus/common/ui/Notifications';
import store from '@dashboards/payments/store';

import Signup from './signup';

const SignupWrap = () => {
  return (
    <Provider store={store}>
      <Router>
        <LayerProvider>
          <ReCaptchaV3Provider reCaptchaKey={window.RECAPTCHA_V3_SITE_KEY}>
            <BladeProvider themeTokens={bladeTheme} colorScheme="light">
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
