import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import QueryString from 'query-string';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignUpWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { ContentContainer } from '../commonStyles';
import { AbsoluteView, RelativeView, Container } from './styles';
import Header from './components/Header';
import InfoContainer from './components/InfoContainer';
import { setCookie } from 'common/utils/cookies';

export const getURLQueryParams = (url = document.location.hash) => {
  const search = url.split('?')[1];
  let params = {};

  if (search) {
    /* split using '&' as separator
      and get the key value pairs for query params. */
    params = search.split('&').reduce((prev, curr) => {
      const [key, value] = curr.split('=');
      prev[key] = value;
      return prev;
    }, {});
  }

  return params;
};

const SignUp = () => {
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const { auth_source } = getURLQueryParams(window.location.search);
  const isSignUpFromWebsite = auth_source && auth_source === 'website';

  useEffect(() => {
    if (isSignUpFromWebsite) {
      setCookie('auth_source', auth_source);
    }
  }, []);

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

  useEffect(() => {
    try {
      //set recommend product to localstorage.
      const query = QueryString.parse(window.location.search);
      if (query?.recommended_product) {
        localStorage.setItem('merchant_landing_page', query.recommended_product);
      }
    } catch (e) {
      // ignore silently
    }
  }, []);

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

    window.location.href = '/#/access/signin';
  };

  return (
    <ThemeProvider theme={theme}>
      <Size minHeight="100vh">
        <Container>
          <Size maxWidth="830px" height="100%">
            <Flex flexDirection="column">
              <ContentContainer>
                <Header
                  handleOnClick={handleLoginClick}
                  isSignUpFromWebsite={isSignUpFromWebsite}
                />
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
