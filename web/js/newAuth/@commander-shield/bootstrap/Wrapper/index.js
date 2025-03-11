import React from 'react';
import PropTypes from 'prop-types';
import styled, { ThemeProvider } from 'styled-components';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { GoogleReCaptchaProvider as ReCaptchaV3Provider } from 'react-google-recaptcha-v3';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import ErrorBoundary from '../../shared/ErrorBoundary';
import GlobalStyles from '../GlobalStyles';
import SnackbarProvider from '../../shared/Snackbar/SnackbarProvider';
import UserProvider from '../../user/UserProvider';
import CommanderShieldThemeWrapper from 'newAuth/commanderShieldThemeWrapper';
import { commanderShieldConfig } from '../../config/config';

const __ENV__ = commanderShieldConfig[SHIELD_STAGE];

const Container = styled(View)`
  position: relative;
  overflow: hidden;
  font-family: ${(props) => props.theme.fonts.family.lato.regular} serif;
  height: 100%;
  margin: 0;
  box-sizing: border-box;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;

  > *,
  *::after,
  *::before {
    box-sizing: inherit;
  }
`;

const RouterValidator = ({ children, disableRouter }) => {
  if (disableRouter) {
    return <>{children}</>;
  }
  return <Router>{children}</Router>;
};

const Wrapper = ({ theme, children, disableRouter }) => {
  return (
    <CommanderShieldThemeWrapper>
      <ThemeProvider theme={theme}>
        <ReCaptchaV3Provider reCaptchaKey={__ENV__.v3Captcha.key}>
          <GlobalStyles />
          <ErrorBoundary>
            <RouterValidator disableRouter={disableRouter}>
              <Size height="100%">
                <Flex flexDirection="column">
                  <Container>
                    <UserProvider>
                      <SnackbarProvider>
                        <Flex flexGrow={1}>
                          <Routes>
                            <Route path="*" element={<>{children}</>} />
                          </Routes>
                        </Flex>
                      </SnackbarProvider>
                    </UserProvider>
                  </Container>
                </Flex>
              </Size>
            </RouterValidator>
          </ErrorBoundary>
        </ReCaptchaV3Provider>
      </ThemeProvider>
    </CommanderShieldThemeWrapper>
  );
};

Wrapper.propTypes = {
  children: PropTypes.node,
  theme: PropTypes.object,
};

export default Wrapper;
