import React, { useState, useEffect } from 'react';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from 'newAuth/@commander-shield/bootstrap/SignUpWrapper';
import QueryString from 'query-string';
import { ThemeProvider } from 'styled-components';
import { FullPageLoader } from 'newAuth/@deprecated/common/components/Loader'; // eslint-disable-line
import { isProductionEnv, setCookie } from '@libs/shared-utils';
import { fetchOrg } from 'newAuth/apis';
import { ContentContainer } from 'newAuth/commonStyles';
import {
  getURLQueryParams,
  isPasswordUXImprovementEnabled,
  isTestEnvironment,
  getHostName,
} from 'newAuth/utils';

import Header from './components/Header';
import InfoContainer from './components/InfoContainer';
import RefereeBanner from './components/RefereeBanner';
import { AbsoluteView, RelativeView, Container } from './styles';

const SignUp = () => {
  const [orgName, setOrgName] = useState();
  const [isFetchingOrgData, setFetchingOrgData] = useState(false);
  const [captchaDisabled, setDisabledCaptcha] = useState(false);
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const { auth_source, r: query_reference } = getURLQueryParams(window.location.search);
  const isSignUpFromWebsite = auth_source && auth_source === 'website';
  const isSigningUpAsPartner = query_reference === 'partner';

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
        window?.localStorage.setItem('merchant_landing_page', query.recommended_product);
      }
    } catch (e) {
      // ignore silently
    }
  }, []);

  useEffect(() => {
    /*
    Event snippet for Signuppage on https://dashboard.razorpay.com/signup: Please do not remove.
    Place this snippet on pages with events you’re tracking.
    Creation date: 12/06/2021
    */
    if (!window.gtag) return;
    // eslint-disable-next-line
    gtag('event', 'conversion', {
      allow_custom_scripts: true,
      send_to: 'DC-11482329/pbsign/signu0+unique',
    });
  }, []);

  const getOrgData = () => {
    setFetchingOrgData(true);
    fetchOrg()
      .then((res) => {
        setOrgName(res?.data?.custom_code);
        if (res?.data?.configurations?.disable_captcha) {
          setDisabledCaptcha(true);
        }
        setFetchingOrgData(false);
      })
      .catch(() => {
        setFetchingOrgData(false);
      });
  };

  useEffect(() => {
    const query = QueryString.parse(window.location.search);
    if (getHostName() !== 'dashboard.razorpay.com' || query?.merchant_invitation) {
      getOrgData();
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

  if (isSigningUpAsPartner) {
    const partnerSignupUrl = isProductionEnv()
      ? 'https://accounts.razorpay.com/auth/?auth_intent=signup&user_type=partner'
      : 'https://accounts.np.razorpay.in/auth/?auth_intent=signup&user_type=partner';

    window.location.href = partnerSignupUrl;
    return null;
  }

  return (
    <ThemeProvider theme={theme}>
      {isFetchingOrgData ? (
        <FullPageLoader />
      ) : (
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
                    <RefereeBanner />
                    <AbsoluteView>
                      <Auth
                        appName="dashboard"
                        authClientId={window.OAUTH_CLIENT_ID}
                        oneTapInfo={oneTapInfo}
                        showPasswordRules={isPasswordUXImprovementEnabled()}
                        skipCaptcha={isTestEnvironment() || captchaDisabled}
                        autoReadOtpSignup
                        showMobileSignup
                        orgName={orgName}
                      />
                    </AbsoluteView>
                    <InfoContainer handleContactUsClick={handleContactUsClick} />
                  </RelativeView>
                </ContentContainer>
              </Flex>
            </Size>
          </Container>
        </Size>
      )}
    </ThemeProvider>
  );
};
export default SignUp;
