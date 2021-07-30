import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React, { useState, useEffect } from 'react';
import { render } from 'react-dom';
import { ThemeProvider } from 'styled-components';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import SignUp from './signup';
import SignIn from './signin';
import { ROUTES } from './utils';

__webpack_public_path__ = `${window.cdnDashboardUrl || ''}/dist/`;

const App = () => {
  const location = window.location.pathname.substr(1);
  const [authType, setAuthType] = useState(location);

  const handleRouteChange = (route) => {
    setAuthType(route);
  };

  return (
    <ThemeProvider theme={theme}>
      <Size height="100%">
        {authType.includes(ROUTES.SIGNUP) ? (
          <SignUp onRouteChange={handleRouteChange} />
        ) : (
          <SignIn onRouteChange={handleRouteChange} />
        )}
      </Size>
    </ThemeProvider>
  );
};

render(<App />, document.getElementById('react-root'));
