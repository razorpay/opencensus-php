import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React, { useState, useEffect } from 'react';
import { render } from 'react-dom';
import Styled, { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import SignUp from '@commander/shield/src/bootstrap/Wrapper/Wrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@commander/shield/src/shared/Button';
import Link from '@commander/shield/src/shared/Link';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { getURLQueryParams } from '../common/utils/rzp-utils';

__webpack_public_path__ = `${window.cdnDashboardUrl || ''}/dist/`;

let BANNER_TEXT = 'Special NEO pricing plan has been applied. Complete your sign up now';

const NEO_COUPON = 'NEORZP';
const NEW_YEAR_COUPON = 'NEWYEAR21';

const { r } = getURLQueryParams(window.location.search);
const isPartner = r === 'partner';

const Container = Styled(View)`
  overflow-y: auto;
  background: linear-gradient(0deg, rgba(2, 42, 156, 0.3), rgba(2, 42, 156, 0.3)), linear-gradient(232.85deg, #020529 -52%, #000B8E 198.1%);
`;

const ContentContainer = Styled(View)`
  margin: 0 auto;
  @media (min-width: 415px) {
    display: block;
  }
  @media (min-width: 769px) and (max-width: 900px) {
    padding: 0 32px;
  }
`;

const CustomLink = Styled(Link)`
  color: ${({ theme }) => theme.colors.shade[950]};
  text-decoration: underline;
  &&:visited {
    color: ${({ theme }) => theme.colors.shade[950]};
  }
  &&:hover {
    color: ${({ theme }) => theme.colors.shade[950]};
  }
  &&:active {
    color: ${({ theme }) => theme.colors.shade[950]};
  }
`;

const HeaderView = Styled(View)`
  background: linear-gradient(149.39deg, #2B4486 0%, #0B70E7 100%);
  flex-shrink: 0;
  &&& {
    padding: 0;
    height: 73px;
    align-items: center;
    justify-content: center;
    @media (min-width: 415px) {
      background: none;
      height: auto;
      padding: 48px 0 60px 0;
      justify-content: center;
      padding: 48px 42px 60px 42px;
    }
    @media (min-width: 769px) {
      justify-content: space-between;
      padding: 32px 0 36px 0;
    }
  }
`;

const AbsoluteView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  border-radius: 2px;
  margin: 0 auto;
  flex-grow: 1;
  height: 100%;
  max-width: 100%;
  background-color: ${({ theme }) => theme.colors.background[100]};
  @media (min-width: 415px) {
    height: 577px;
    max-width: 375px;
  }
  @media (min-width: 769px) {
    flex-grow: initial;
    position: absolute;
    left: 32px;
    top: -48px;
    width: 320px;
    height: calc(100% + 96px);
  }
`;

const RelativeView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  height: calc(100% - 73px);
  border-radius: 4px;
  display: flex;
  flex-grow: 1;
  @media (min-width: 415px) {
    height: auto;
    margin-bottom: 30px;
  }
  @media (min-width: 769px) {
    height: 480px;
    flex-grow: initial;
    display: block;
    position: relative;
    background: ${({ theme }) => theme.colors.background[400]};
    padding-left: 384px;
    margin: 48px 0;
  }
`;

const BorderView = Styled(View)`
  width: 50px;
  height: 2px;
  position relative;
  left: 50%;
  margin-left: -25px;
  background: ${({ theme }) => theme.colors.shade[920]};
`;

const DesktopOnlyView = Styled(View)`
  display: none;
  @media (min-width: 769px) {
    display: block;
  }
`;

const MobileOnlyView = Styled(View)`
  display: none;
  @media (max-width: 768px) {
    display: block;
  }
`;

const CustomLoginButton = Styled(Button)`
  background-color: ${({ theme }) => theme.colors.background[100]};
  border: 1px solid ${({ theme }) => theme.colors.background[100]};
  > div{
   color: ${({ theme }) => theme.colors.primary[800]};
  }
  :hover, :focus{
   background-color: ${({ theme }) => theme.colors.background[800]};
   border: 1px solid ${({ theme }) => theme.colors.background[800]};
  }
  :active{
   background-color: ${({ theme }) => theme.colors.background[600]};
   border: 1px solid ${({ theme }) => theme.colors.background[600]};
   }
`;

const DesktopBannerBg = Styled(View)`
  background: url('/dist/css/assets/banner.svg')
`;

const MobileBannerBg = Styled(View)`
  background: rgba(65, 164, 19, 0.03);
`;

const MobileBannerView = Styled(View)`
  display: none;
  @media (max-width: 767px) {
    display: block;
  }
`;

const DesktopBannerView = Styled(View)`
  display: none;
  @media (min-width: 768px) {
    display: block;
  }
`;

const FullHeightFlex = Styled(Flex)`
  height: 100%;
`;

const InlineText = Styled(Text)`
  display: inline;
`;

const App = () => {
  const [showBanner, setShowBanner] = useState(false);
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts[1].split(';')[0];
    return null;
  };
  const couponCode = getCookie('couponCode');

  const isNEOCouponApplied = () => {
    return (
      couponCode &&
      (couponCode.toUpperCase() === NEO_COUPON || couponCode.toUpperCase() === NEW_YEAR_COUPON) &&
      !isPartner
    );
  };

  const handleRouteChange = () => {
    if (!isNEOCouponApplied()) {
      setShowBanner(false);
    }
  };

  // @Todo: show/hide banner based on campaign end date
  // useEffect(() => {
  //   if (!window.location.href.includes('coupon_code') && isNEOCouponApplied()) {
  //     if (couponCode.toUpperCase() === NEW_YEAR_COUPON) {
  //       BANNER_TEXT = 'Special new year pricing plan has been applied. Complete your sign up now';
  //     }
  //     setShowBanner(true);
  //   }
  // });

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
      eventCategory: 'Signup - Steps',
      eventAction: 'Click - Contact Us',
    });
  };

  const handleLoginClick = () => {
    window.rzpQ.push(
      window.rzpQ.now().onbr().initiated('signup.secondary_links', { source: 'Login' }),
    );

    window.rzpAnalytics({
      eventCategory: 'Signup - Email Password',
      eventAction: 'Click - Login',
    });

    window.location.href = '/#/access/signin';
  };

  const MobileBanner = () => {
    return (
      <MobileBannerView>
        <Space padding={[1.5, 4]}>
          <MobileBannerBg>
            <InlineText color="positive.900" size="small">
              {BANNER_TEXT}
            </InlineText>
          </MobileBannerBg>
        </Space>
      </MobileBannerView>
    );
  };

  const DesktopBanner = () => {
    return (
      <DesktopBannerView>
        <Flex justifyContent="center">
          <Space padding={[1.75, 0, 1.75, 0]}>
            <DesktopBannerBg>
              <InlineText color="background.100">{BANNER_TEXT}</InlineText>
            </DesktopBannerBg>
          </Space>
        </Flex>
      </DesktopBannerView>
    );
  };

  return (
    <ThemeProvider theme={theme}>
      <Size height="100%">
        <Container>
          {showBanner && <DesktopBanner />}
          <Size maxWidth="830px" height="100%">
            <Flex flexDirection="column">
              <ContentContainer>
                <DesktopOnlyView>
                  <Flex justifyContent="space-between">
                    <HeaderView>
                      <Size maxWidth="150px">
                        <img src="/img/logo_full.png" alt="Razorpay" />
                      </Size>
                      <Flex alignItems="center">
                        <View>
                          <Text color="background.100" weight="bold">
                            Already a user?
                          </Text>
                          <Space margin={[0, 0, 0, 2.25]}>
                            <Button onClick={handleLoginClick}>Log In</Button>
                          </Space>
                        </View>
                      </Flex>
                    </HeaderView>
                  </Flex>
                </DesktopOnlyView>

                <MobileOnlyView>
                  <HeaderView>
                    <FullHeightFlex justifyContent="space-around" alignItems="center">
                      <View>
                        <Size maxWidth="120px">
                          <img src="/img/logo_full.png" alt="Razorpay" />
                        </Size>
                        <Space margin={[3, 0, 0, 8]}>
                          <CustomLoginButton size="small" onClick={handleLoginClick}>
                            Log in
                          </CustomLoginButton>
                        </Space>
                      </View>
                    </FullHeightFlex>
                  </HeaderView>
                </MobileOnlyView>

                <RelativeView>
                  <AbsoluteView>
                    <SignUp
                      appName="dashboard"
                      header={showBanner && <MobileBanner />}
                      onRouteChange={handleRouteChange}
                      authClientId={window.OAUTH_CLIENT_ID}
                      oneTapInfo={oneTapInfo}
                    />
                  </AbsoluteView>
                  <DesktopOnlyView>
                    <Space padding={[8, 5.5, 4, 0]}>
                      <View>
                        <Flex flexDirection="column" justifyContent="center" alignItems="center">
                          <View>
                            <Space margin={[0, 0, 1, 0]}>
                              <Text size="large" weight="bold">
                                Why choose Razorpay?
                              </Text>
                            </Space>
                            <Text size="small" color="shade.960">
                              50,00,000+ businesses trust their payments with Razorpay
                            </Text>
                          </View>
                        </Flex>
                        <Space margin={[3.5, 0, 3.75, 0]}>
                          <Size maxWidth="100%">
                            <img src="/img/client-logos.png" alt="Clients" />
                          </Size>
                        </Space>
                        <BorderView />
                        <Space margin={[2.5, 0, 0, 0]}>
                          <Flex justifyContent="center">
                            <View>
                              <Space margin={[0, 0.5, 0, 0]}>
                                <Text as="span" size="xsmall" color="shade.960">
                                  Need help? We are just a click away.
                                </Text>
                              </Space>
                              <CustomLink
                                size="xsmall"
                                href="https://razorpay.com/support/#request/merchant"
                                target="_blank"
                                onClick={handleContactUsClick}
                              >
                                Contact Us
                              </CustomLink>
                            </View>
                          </Flex>
                        </Space>
                      </View>
                    </Space>
                  </DesktopOnlyView>
                </RelativeView>
              </ContentContainer>
            </Flex>
          </Size>
        </Container>
      </Size>
    </ThemeProvider>
  );
};

render(<App />, document.getElementById('react-root'));
