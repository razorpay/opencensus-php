import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignUpWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { ROUTES } from '../utils';
import { ContentContainer } from '../commonStyles';
import { AbsoluteView, RelativeView, Container } from './styles';
import Header from './components/Header';
import InfoContainer from './components/InfoContainer';

const SignUp = ({ onRouteChange }) => {
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });

  useEffect(() => {
    // check if Google Onetap script has successfully loaded or failed to load
    const oneTapInfoInterval = setInterval(() => {
      if (window.isOneTapScriptFailed !== undefined) {
        clearInterval(oneTapInfoInterval);
        setOneTapInfo({
          isExpOn: true,
          isScriptFailed: window.isOneTapScriptFailed,
        });
      }
    }, 200);

    return () => {
      clearInterval(oneTapInfoInterval);
    };
  }, [setOneTapInfo]);

  const handleContactUsClick = () => {
    window.rzpQ.push(
      window.rzpQ.now().onbr().initiated('signup.secondary_links', { source: 'Contact us' }),
    );
    window.rzpAnalytics({
      eventCategory: 'Index - Steps',
      eventAction: 'Click - Contact Us',
    });
  };

  const handleLoginClick = () => {
    window.rzpQ.push(
      window.rzpQ.now().onbr().initiated('signup.secondary_links', { source: 'Login' }),
    );

    window.rzpAnalytics({
      eventCategory: 'Index - Email Password',
      eventAction: 'Click - Login',
    });

    onRouteChange(ROUTES.SIGNIN);
  };

  return (
    <ThemeProvider theme={theme}>
      <Size height="100%">
        <Container>
          <Size maxWidth="830px" height="100%">
            <Flex flexDirection="column">
              <ContentContainer>
                <Header handleOnClick={handleLoginClick} />
                <RelativeView>
                  <AbsoluteView>
                    <Auth
                      appName="dashboard"
                      authClientId={window.OAUTH_CLIENT_ID}
                      oneTapInfo={oneTapInfo}
                    />
                  </AbsoluteView>
                  <InfoContainer handleContactUsClick={handleContactUsClick} />
                </RelativeView>
              </ContentContainer>
            </Flex>
          </Size>
        </Container>
      </Size>
    </ThemeProvider>
  );
};
export default SignUp;
