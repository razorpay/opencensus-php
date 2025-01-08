import React, { useEffect, useState } from 'react';
import { Box, Card, CardBody, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import styled from 'styled-components';
import SideNavigation from 'merchant/components/NavigationLayout/SideNavigation/SideNavigation';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import BottomNavigation from './BottomNavigation/BottomNavigation';
import NavigationContent from './NavigationContent/NavigationContent';
import TopNavigation from './TopNavigation/TopNavigation';
import { NavigationLayoutContext } from './context';
import Footer from '../Footer';
import HeaderNav from '../HeaderNav';
import Sidebar from '../Sidebar';
import SidebarV2 from '../SidebarV2';
import useConnectedProducts from './hooks/useConnectedProducts';
import { useStore } from 'shell/commonStore';
import UniversalSearch from '../HeaderNav/UniversalSearch';
import { getItem, setItem } from 'common/utils/localStorage';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { HOTJAR_TRIGGERS } from './constants';

const DashboardBackground = styled.div(() => {
  return {
    height: '100vh',
    background: '#E3EAF3',
  };
});

function NavigationLayout({
  renderFullPageView,
  isWebView,
  showSidebarV2,
  headerProps,
  sidebarProps,
  windowWidth,
  user,
  children,
  isRTUXHomepage,
  isConnectedNavigation,
}): JSX.Element {
  const [isSideNavOpenOnMobile, setIsSideNavOpenOnMobile] = useState(false);
  const org = useStore((state) => state.session.org);

  const showHeaderInWebview = () => {
    if (isWebView && isJKOfflineMerchant(org, user)) {
      return true;
    }

    return !isWebView;
  };

  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  useEffect(() => {
    if (isConnectedNavigation) {
      const userSeenConnectedNavigation = (viewType: string) => {
        setItem('conn-nav-viewed', viewType);
        triggerHotjarRecording(viewType, [viewType]);
      };
      const connNavViewed = getItem('conn-nav-viewed');
      const viewType = !connNavViewed
        ? HOTJAR_TRIGGERS.CONNECTED_NAV_FIRST
        : HOTJAR_TRIGGERS.CONNECTED_NAV_REPEAT;
      userSeenConnectedNavigation(viewType);
    }
  }, []);

  if (isConnectedNavigation) {
    const commonProps = {
      renderFullPageView,
      user,
      org,
    };

    return (
      <NavigationLayoutContext.Provider
        value={{
          isSideNavOpenOnMobile,
          setIsSideNavOpenOnMobile,
          onSwitchMode: headerProps.onSwitchMode,
          isConnectedNavigation,
        }}
      >
        <DashboardBackground>
          <TopNavigation {...commonProps} {...headerProps} />
          <Box
            marginX={{ base: 'spacing.0', m: 'spacing.3' }}
            borderTopLeftRadius="medium"
            overflow="hidden"
            position="relative"
          >
            <SideNavigation {...commonProps} />
            <NavigationContent {...commonProps}>{children}</NavigationContent>

            {/* TODO: Hiding for initial release */}
            {/* <BottomNavigation /> */}
          </Box>
        </DashboardBackground>
      </NavigationLayoutContext.Provider>
    );
  }

  return (
    <>
      {!renderFullPageView && showHeaderInWebview() && (
        <>
          <HeaderNav {...headerProps} />
          {showSidebarV2 ? <SidebarV2 {...sidebarProps} /> : <Sidebar {...sidebarProps} />}
        </>
      )}

      {children}
      {!renderFullPageView && !isWebView && (
        <Footer showMobileNav={windowWidth < 950} user={user} />
      )}
    </>
  );
}

export default NavigationLayout;
