import 'core-js/es/map';
import 'core-js/es/set';
import 'regenerator-runtime/runtime.js'; // eslint-disable-line

import { initSentry } from 'common/utils/observability';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import React, { Suspense, useEffect } from 'react';
import { render } from 'react-dom';
import { createGlobalStyle, ThemeProvider } from 'styled-components';
import splitz from './splitz';
import { ROUTES } from './utils';
import { FullPageLoader } from 'newAuth/@deprecated/common/components/Loader'; // eslint-disable-line
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';

initSentry?.('newauth-dashboard');

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

try {
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

  render(<App />, document.getElementById('react-root'));
} catch (error) {
  errorService?.captureError?.(error, {
    rank: DASHBOARD_PRIORITY_RANKS?.P0,
    tags: {
      type: 'P0: Critical Incident',
      app: 'newauth-dashboard',
    },
  });
}
