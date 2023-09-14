import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import QueryString from 'query-string';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignUpWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { ContentContainer } from 'newAuth/commonStyles';
import { AbsoluteView, RelativeView, Container, DisableSignupContainer } from './styles';
import Header from './components/Header';
import InfoContainer from './components/InfoContainer';
import RefereeBanner from './components/RefereeBanner';
import {
  getURLQueryParams,
  isPasswordUXImprovementEnabled,
  isTestEnvironment,
  getHostName,
} from 'newAuth/utils';
import { setCookie } from 'common/utils/cookies';
import CommanderShieldThemeWrapper from 'newAuth/commanderShieldThemeWrapper';
import { fetchOrg } from 'newAuth/apis';
import { FullPageLoader } from 'common/components/Loader';
import PartnerSignup from './components/PartnerSignup';
import { Modal, ModalBody } from 'common/components/Modal';
import { ModalHeader, ModalFooter } from 'common/components/Modal/Styled';
import { Button } from '@razorpay/blade/components';
import { trackWithSegment } from 'newAuth/trackEvents';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { isNewPartnerSignup, isSignupEnabled, isShowResumeOnboarding } from 'newAuth/splitz/index';

const SignUp = () => {
  const [programDsCheck, setProgramDsCheck] = useState(false);
  const [orgName, setOrgName] = useState();
  const [isFetchingOrgData, setFetchingOrgData] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [captchaDisabled, setDisabledCaptcha] = useState(false);
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const {
    auth_source,
    r: query_reference,
    invitation,
    merchant_invitation,
  } = getURLQueryParams(window.location.search);
  const isSignUpFromWebsite = auth_source && auth_source === 'website';
  const isSigningUpAsPartner = query_reference === 'partner';

  useEffect(() => {
    if (isSignUpFromWebsite) {
      setCookie('auth_source', auth_source);
    }

    // show partner onboarding resumed notification modal
    setIsOpen(true);
    const objectName = isSigningUpAsPartner
      ? 'Partner Onboarding Paused Modal'
      : 'SubM Onboarding Paused Modal';
    trackWithSegment({
      objectName,
      actionName: 'Loaded',
      location: '',
      properties: {
        mobileSignup: isNewPartnerSignup(),
      },
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
        const isProgramDsCheck = res?.data?.features?.indexOf('program_ds_check') > -1;
        setOrgName(res?.data?.custom_code);
        if (res?.data?.configurations?.disable_captcha) {
          setDisabledCaptcha(true);
        }
        setProgramDsCheck(isProgramDsCheck);
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

  const onClose = () => {
    setIsOpen(false);
    const objectName = isSigningUpAsPartner
      ? 'Partner Onboarding Paused Modal Continue'
      : 'SubM Onboarding Paused Modal Continue';
    trackWithSegment({
      objectName,
      actionName: 'Clicked',
      location: '',
      properties: {
        mobileSignup: isNewPartnerSignup(),
      },
    });
  };

  // enable signup for invitation merchant
  let disableSignup = !invitation && !programDsCheck && !merchant_invitation;

  // Enable signup for curlec.com (Malaysia)
  if (window.location.host === 'dashboard.curlec.com') {
    disableSignup = false;
  }

  // splitz experiment to check if signup is enabled or disabled, should be removed when signup is enabled for all merchants
  const signupEnabled = isSignupEnabled();

  if (signupEnabled) {
    disableSignup = false;
  }

  if (!disableSignup && isSigningUpAsPartner && isNewPartnerSignup())
    return (
      <>
        <PartnerSignup />
        {isShowResumeOnboarding() ? null : (
          <Modal
            isOpen={isOpen}
            onClose={onClose}
            bottomsheet={isMobileAndTablet()}
            bottomSheetHeight="265px"
          >
            <ModalHeader>New business onboarding is temporarily paused</ModalHeader>
            <ModalBody>
              Please submit your details so that your partner account can be activated at the
              earliest when we resume onboarding.
              <br />
              *You can keep referring your clients in the meanwhile
            </ModalBody>
            <ModalFooter>
              <Button onClick={onClose}>Continue</Button>
            </ModalFooter>
          </Modal>
        )}
      </>
    );

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
                  {/* Temprorary disable signup */}
                  {disableSignup ? (
                    <DisableSignupContainer>
                      <Text size="large" weight="bold" align="center">
                        We are under scheduled maintenance.
                        <br /> Apologies for the inconvenience.
                      </Text>
                    </DisableSignupContainer>
                  ) : (
                    <RelativeView>
                      <RefereeBanner />
                      <AbsoluteView>
                        <CommanderShieldThemeWrapper>
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
                        </CommanderShieldThemeWrapper>
                      </AbsoluteView>

                      <InfoContainer handleContactUsClick={handleContactUsClick} />
                    </RelativeView>
                  )}
                  <Modal
                    isOpen={isOpen}
                    onClose={onClose}
                    bottomsheet={isMobileAndTablet()}
                    bottomSheetHeight="265px"
                  >
                    <ModalHeader>New business onboarding is temporarily paused</ModalHeader>
                    <ModalBody>
                      {isSigningUpAsPartner ? (
                        <>
                          Please submit your details so that your partner account can be activated
                          at the earliest when we resume onboarding.
                          <br />
                          *You can keep referring your clients in the meanwhile
                        </>
                      ) : (
                        <>
                          Please submit your details so that your merchant account can be activated
                          at the earliest when we resume onboarding.
                        </>
                      )}
                    </ModalBody>
                    <ModalFooter>
                      <Button onClick={onClose}>Continue</Button>
                    </ModalFooter>
                  </Modal>
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
