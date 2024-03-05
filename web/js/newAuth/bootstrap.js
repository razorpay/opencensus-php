import Size from '@razorpay/blade-old/src/atoms/Size';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import 'core-js/es/map';
import 'core-js/es/set';
import React, { Suspense, useEffect } from 'react';
import { render } from 'react-dom';
import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import { createGlobalStyle, ThemeProvider } from 'styled-components';

import splitz from './splitz';
import { ROUTES } from './utils';
import { FullPageLoader } from '../common/components/Loader'; // eslint-disable-line

const SignIn = React.lazy(() => import('./signin'));
const SignUp = React.lazy(() => import('./signup'));
const ResetPassword = React.lazy(() => import('./resetPassword'));
const EmailUpdate = React.lazy(() => import('./emailUpdate'));

const isSupportedPath = (pathname) => ['', ...Object.values(ROUTES)].indexOf(pathname) > -1;

const GlobalStyle = createGlobalStyle`
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  html, body {
    height: 100%;
    margin: 0;
  }
  
  div#react-root > div:first-child {
    height: 100%;
  }
`;

const App = () => {
  const route = window.location.pathname.substring(1);

  useEffect(() => {
    if (!isSupportedPath(route)) {
      location.href = '/signin';
    }
  }, [route]);

  useEffect(() => {
    splitz();
  }, []);

  const getComponentBasedOnRoute = (route) => {
    switch (route) {
      case '':
      case ROUTES.SIGNIN:
        return <SignIn />;
      case ROUTES.SIGNUP:
        return <SignUp />;
      case ROUTES.RESETPASSWORD:
        return <ResetPassword />;
      case ROUTES.EMAIL_UPDATE:
        return <EmailUpdate />;
      default:
        return <div />;
    }
  };

  return isSupportedPath(route) ? (
    <ThemeProvider theme={theme}>
      <GlobalStyle />
      <Suspense fallback={<FullPageLoader />}>
        <Size height="100%">{getComponentBasedOnRoute(route)}</Size>
      </Suspense>
    </ThemeProvider>
  ) : (
    <div />
  );
};

if (module.hot) {
  module.hot.accept();
}

render(<App />, document.getElementById('react-root'));
