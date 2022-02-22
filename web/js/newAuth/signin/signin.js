import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignInWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { fetchOrg, transformFetchOrgData } from './apis';
import { getBankingCaptchaColor, getTheme } from './theme';
import { BANK_NAMES, getHostName, isTestEnvironment } from '../utils';
import { DesktopOnlyView } from '../commonStyles';
import {
  Container,
  AbsoluteView,
  RelativeView,
  Image,
  ContentContainer,
  LinkButton,
  CaptchaTextView,
  CaptchaText,
} from './styles';
import DefaultView from './components/DefaultView';
import OrgView from './components/OrgView';
import Header from './components/Header';
import { FullPageLoader } from '../../common/components/Loader';

const DEFAULT_ORG_DATA = {
  display_name: 'Razorpay Software Private Ltd',
  business_name: 'Razorpay',
  allow_sign_up: true,
  checkout_logo_url: 'https://cdn.razorpay.com/logo.png',
  email_logo_url: null,
  custom_code: 'rzp',
  background_image_url: null,
  second_factor_auth_mode: 'sms',
  hostname: 'dashboard.razorpay.com',
};

const Signin = () => {
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const [orgData, setOrgData] = useState({});
  const [isFetchingOrgData, setFetchingOrgData] = useState(true);

  useEffect(() => {
    // Only fetch org data if it's a banking url
    if (getHostName() === DEFAULT_ORG_DATA.hostname) {
      setOrgData(transformFetchOrgData(DEFAULT_ORG_DATA));
      setFetchingOrgData(false);
      return;
    }
    setFetchingOrgData(true);
    fetchOrg()
      .then((res) => {
        const response = transformFetchOrgData(res.data);
        if (response.orgName === BANK_NAMES.KKBK) {
          // Kotak bank's backgroundImageUrl is showing a blank white image
          // thus we are removing it, which will result in fallback of .logo being in use
          response.backgroundImgUrl = null;
        }
        setOrgData(response);
        setFetchingOrgData(false);
      })
      .catch(() => {
        setFetchingOrgData(false);
      });
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

  const handleSignUpClick = () => {
    window.rzpQ.push(
      window.rzpQ.now().onbr().initiated('login.non_login_actions', {
        action: 'click sign up',
      }),
    );
    window.analytics.track('Login Non Login Actions Initiated', { action: 'click sign up' });

    window.location.href = '/signup';
  };

  const captchaTextColor = getBankingCaptchaColor(orgData.orgName);

  return (
    <ThemeProvider theme={theme}>
      {isFetchingOrgData ? (
        <FullPageLoader />
      ) : (
        <Size minheight="100vh">
          <Container org={orgData.orgName}>
            {orgData.backgroundImgUrl && <Image src={orgData.backgroundImgUrl} />}
            <Size maxWidth="830px">
              <Flex flexDirection="column">
                <ContentContainer>
                  {!orgData.backgroundImgUrl && (
                    <Header handleOnClick={handleSignUpClick} orgData={orgData} /> // dont show logo as header when background url is present
                  )}
                  <RelativeView>
                    <DesktopOnlyView>
                      <Space padding={[5]}>
                        <View>
                          {orgData.isOrgRZP ? <DefaultView /> : <OrgView orgData={orgData} />}
                        </View>
                      </Space>
                    </DesktopOnlyView>

                    <AbsoluteView>
                      <Auth
                        appName="dashboard"
                        authClientId={window.OAUTH_CLIENT_ID}
                        oneTapInfo={oneTapInfo}
                        theme={getTheme(orgData.orgName)}
                        isGoogleOauthEnabled={orgData.orgName !== BANK_NAMES.AXIS}
                        skipCaptcha={isTestEnvironment()}
                      />
                      <CaptchaTextView>
                        <Flex>
                          <Space padding={[2, 0]} margin="auto">
                            <Size maxWidth="232px">
                              <Flex flexWrap="wrap" justifyContent="center">
                                <View>
                                  <CaptchaText textColor={captchaTextColor.primary} size="xsmall">
                                    Protected by reCAPTCHA. Google
                                  </CaptchaText>
                                  <Space padding={[0, 0.25]}>
                                    <LinkButton
                                      as="a"
                                      href="https://policies.google.com/privacy"
                                      target="_blank"
                                    >
                                      <CaptchaText
                                        textColor={captchaTextColor.secondary}
                                        size="xsmall"
                                      >
                                        Privacy Policy
                                      </CaptchaText>
                                    </LinkButton>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <CaptchaText textColor={captchaTextColor.primary} size="xsmall">
                                      &
                                    </CaptchaText>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <LinkButton
                                      as="a"
                                      href="https://policies.google.com/terms"
                                      target="_blank"
                                    >
                                      <CaptchaText
                                        textColor={captchaTextColor.secondary}
                                        size="xsmall"
                                      >
                                        Terms of Service
                                      </CaptchaText>
                                    </LinkButton>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <CaptchaText textColor={captchaTextColor.primary} size="xsmall">
                                      apply.
                                    </CaptchaText>
                                  </Space>
                                </View>
                              </Flex>
                            </Size>
                          </Space>
                        </Flex>
                      </CaptchaTextView>
                    </AbsoluteView>
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
export default Signin;
