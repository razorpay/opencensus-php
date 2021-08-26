import React, { useState, useEffect } from 'react';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import Auth from '@razorpay/commander-shield/src/bootstrap/SignInWrapper';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { fetchOrg, transformFetchOrgData } from './apis';
import { THEME as themeColor } from './theme';
import { ROUTES } from '../utils';
import { DesktopOnlyView, ContentContainer } from '../commonStyles';
import { Container, AbsoluteView, RelativeView } from './styles';
import DefaultView from './components/DefaultView';
import OrgView from './components/OrgView';
import Header from './components/Header';

const Signin = ({ onRouteChange }) => {
  const [oneTapInfo, setOneTapInfo] = useState({
    isExpOn: true,
    isScriptFailed: window.isOneTapScriptFailed,
  });
  const [orgData, setOrgData] = useState({});
  // @TODO: Remove this after we go live
  const showNewSignIn = new URLSearchParams(window.location.search).get('newSignIn');

  useEffect(() => {
    fetchOrg()
      .then((res) => {
        const response = transformFetchOrgData(res.data);
        setOrgData(response);
      })
      .catch((error) => {
        console.log('error', error);
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
    onRouteChange(ROUTES.SIGNUP);
  };

  if (!showNewSignIn) {
    return null;
  }

  return (
    <ThemeProvider theme={theme}>
      <Size height="100%">
        <Container org={orgData.orgName}>
          <Size maxWidth="830px" height="100%">
            <Flex flexDirection="column">
              <ContentContainer>
                <Header handleOnClick={handleSignUpClick} orgData={orgData} />
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
                      theme={themeColor[orgData.orgName]}
                    />
                  </AbsoluteView>
                </RelativeView>
              </ContentContainer>
            </Flex>
          </Size>
        </Container>
      </Size>
    </ThemeProvider>
  );
};
export default Signin;
