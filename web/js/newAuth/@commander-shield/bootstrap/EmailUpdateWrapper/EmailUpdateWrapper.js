import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { createGlobalStyle } from 'styled-components';
import { Routes, Route } from 'react-router-dom';
import { lightTheme } from '@razorpay/blade-old/src/tokens/theme';
import SnackbarProvider from '../../shared/Snackbar/SnackbarProvider';
import EmailUpdate from '../../screens/EmailUpdate';
import ErrorBoundary from '../../shared/ErrorBoundary';
import initWrapperEvents from '../Wrapper/InitWrapper/initWrapperEvents';
import Wrapper from '../Wrapper';

const GlobalStyles = createGlobalStyle`
  body {
    background-color: #f0f3f4;
  }
  img {
    max-width: 100%;
  }
`;

const EmailUpdateWrapper = ({ theme, orgData, ...props }) => {
  useEffect(() => {
    initWrapperEvents.trackShieldLoad();
  }, []);

  const customTheme = theme ? theme : lightTheme;

  return (
    <Wrapper theme={customTheme}>
      <ErrorBoundary>
        <SnackbarProvider>
          <GlobalStyles />
          <Routes>
            <Route path="*" element={<EmailUpdate {...props} />} />
          </Routes>
        </SnackbarProvider>
      </ErrorBoundary>
    </Wrapper>
  );
};

export default EmailUpdateWrapper;

EmailUpdateWrapper.propTypes = {
  theme: PropTypes.object,
  orgData: PropTypes.object,
  authClientId: PropTypes.string,
  oneTapInfo: PropTypes.object,
  isGoogleOauthEnabled: PropTypes.bool,
};
