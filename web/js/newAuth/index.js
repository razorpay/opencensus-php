import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import React, { Suspense } from 'react';
import { render } from 'react-dom';
import { ThemeProvider, createGlobalStyle } from 'styled-components';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { FullPageLoader } from '../common/components/Loader';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import { ROUTES } from './utils';

__webpack_public_path__ = `${window.cdnDashboardUrl || ''}/dist/`; // eslint-disable-line

const SignIn = React.lazy(() => import('./signin'));
const SignUp = React.lazy(() => import('./signup'));
const ResetPassword = React.lazy(() => import('./resetPassword'));

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
`;

const App = () => {
  const route = window.location.pathname.substring(1);

  const getComponentBasedOnRoute = (route) => {
    switch (route) {
      case ROUTES.SIGNIN:
        return <SignIn />;
      case ROUTES.SIGNUP:
        return <SignUp />;
      case ROUTES.RESETPASSWORD:
        return <ResetPassword />;
      default:
        return '404';
    }
  };

  return (
    <ThemeProvider theme={theme}>
      <GlobalStyle />
      <Suspense fallback={<FullPageLoader />}>
        <Size height="100%">{getComponentBasedOnRoute(route)}</Size>
      </Suspense>
    </ThemeProvider>
  );
};

render(<App />, document.getElementById('react-root'));
