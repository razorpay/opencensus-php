import 'regenerator-runtime/runtime.js'; // eslint-disable-line
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import { render } from 'react-dom';
import { ThemeProvider } from 'styled-components';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import SignUp from './signup';
import SignIn from './signin';
import { ROUTES } from './utils';

__webpack_public_path__ = `${window.cdnDashboardUrl || ''}/dist/`; // eslint-disable-line

const App = () => {
  const location = window.location.pathname.substr(1);

  return (
    <ThemeProvider theme={theme}>
      <Size height="100%">{location.includes(ROUTES.SIGNUP) ? <SignUp /> : <SignIn />}</Size>
    </ThemeProvider>
  );
};

render(<App />, document.getElementById('react-root'));
