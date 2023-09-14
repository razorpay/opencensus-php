import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignInWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { fetchOrg, transformFetchOrgData } from 'newAuth/apis';
import { getBankingCaptchaColor, getTheme } from './theme';
import { BANK_NAMES, getHostName, isTestEnvironment, IGNORE_BG_IMAGES_BANKS } from 'newAuth/utils';
import { DesktopOnlyView } from 'newAuth/commonStyles';
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
import { FullPageLoader } from 'common/components/Loader';
import CommanderShieldThemeWrapper from 'newAuth/commanderShieldThemeWrapper';

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
  const [captchaDisabled, setDisabledCaptcha] = useState(false);
  const [orgData, setOrgData] = useState(
    /** @type {import("./types").TransformedOrgData} */
    ({}),
  );
  const [isFetchingOrgData, setFetchingOrgData] = useState(true);

  useEffect(() => {
    /* dont show loader in case org is razorpay but still 
       fetch org in background to check only for captcha 
       so that it can toggled based on response 
    */
    if (getHostName() === DEFAULT_ORG_DATA.hostname) {
      setOrgData(transformFetchOrgData(DEFAULT_ORG_DATA));
      setFetchingOrgData(false);
    }
    fetchOrg()
      .then((res) => {
        if (res?.data?.configurations?.disable_captcha) {
          setDisabledCaptcha(true);
        }
        const response = transformFetchOrgData(res.data);
        setOrgData(response);
      })
      .finally(() => {
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

  const getBackgroundImage = () => {
    if (IGNORE_BG_IMAGES_BANKS.some((bank) => orgData.orgName === bank)) {
      return null;
    } else if (orgData.backgroundImgUrl) {
      return <Image src={orgData.backgroundImgUrl} alt="background image" />;
    }
    return null;
  };

  const getHeader = () => {
    // if backgroundImgUrl is present we need to hide header
    // Exception: hdfcCollectNow bank

    if (!orgData.backgroundImgUrl) {
      return <Header handleOnClick={handleSignUpClick} orgData={orgData} />;
    } else if (IGNORE_BG_IMAGES_BANKS.some((bank) => orgData.orgName === bank)) {
      return <Header handleOnClick={handleSignUpClick} orgData={orgData} />;
    }
    return null;
  };

  const captchaTextColor = getBankingCaptchaColor(orgData.orgName);

  return (
    <ThemeProvider theme={theme}>
      {isFetchingOrgData ? (
        <FullPageLoader />
      ) : (
        <Size minheight="100vh">
          <Container org={orgData.orgName}>
            {getBackgroundImage()}
            <Size maxWidth="830px">
              <Flex flexDirection="column">
                <ContentContainer>
                  {getHeader()}
                  <RelativeView>
                    <DesktopOnlyView>
                      <Space padding={[5]}>
                        <View>
                          {orgData.isOrgRZP ? <DefaultView /> : <OrgView orgData={orgData} />}
                        </View>
                      </Space>
                    </DesktopOnlyView>

                    <AbsoluteView>
                      <CommanderShieldThemeWrapper>
                        <Auth
                          appName="dashboard"
                          authClientId={window.OAUTH_CLIENT_ID}
                          oneTapInfo={oneTapInfo}
                          theme={getTheme(orgData)}
                          isGoogleOauthEnabled={orgData.orgName !== BANK_NAMES.AXIS}
                          skipCaptcha={isTestEnvironment() || captchaDisabled}
                        />
                      </CommanderShieldThemeWrapper>
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
                                      rel="noreferrer noopener"
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
                                      rel="noreferrer noopener"
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
