import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignInWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { fetchOrg, transformFetchOrgData } from './apis';
import { getTheme } from './theme';
import { BANK_NAMES } from '../utils';
import { DesktopOnlyView } from '../commonStyles';
import { Container, AbsoluteView, RelativeView, Image, ContentContainer } from './styles';
import DefaultView from './components/DefaultView';
import OrgView from './components/OrgView';
import Header from './components/Header';
import { FullPageLoader } from '../../common/components/Loader';

const Signin = () => {
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const [orgData, setOrgData] = useState({});
  const [isFetchingOrgData, setFetchingOrgData] = useState(true);

  useEffect(() => {
    setFetchingOrgData(true);
    fetchOrg()
      .then((res) => {
        const response = transformFetchOrgData(res.data);
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

  return (
    <ThemeProvider theme={theme}>
      {isFetchingOrgData ? (
        <FullPageLoader />
      ) : (
        <Size height="100%">
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
                      />
                      <DesktopOnlyView>
                        <Flex>
                          <Space padding={[2, 0]} margin="auto">
                            <Size maxWidth="232px">
                              <Flex flexWrap="wrap" justifyContent="center">
                                <View>
                                  <Text color="light.970" size="xsmall">
                                    Protected by reCAPTCHA. Google
                                  </Text>
                                  <Space padding={[0, 0.25]}>
                                    <Text color="primary.900" size="xsmall">
                                      Privacy Policy
                                    </Text>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <Text color="light.970" size="xsmall">
                                      &
                                    </Text>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <Text color="primary.900" size="xsmall">
                                      Terms of Service
                                    </Text>
                                  </Space>
                                  <Space padding={[0, 0.25]}>
                                    <Text color="light.970" size="xsmall">
                                      apply.
                                    </Text>
                                  </Space>
                                </View>
                              </Flex>
                            </Size>
                          </Space>
                        </Flex>
                      </DesktopOnlyView>
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
