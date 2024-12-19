import React from 'react';
import { BottomNav, BottomNavItem, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { Link, matchPath, useLocation } from 'react-router-dom';

import { ONE_NAV_MOBILE_PATH, BOTTOM_NAV_ITEMS } from '../constants';
import { useNavigationLayoutContext } from '../context';

//TODO: Check bottom nav for different products (payments/partners) post initial release

const BottomNavigation = () => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const location = useLocation();
  const { setIsSideNavOpenOnMobile } = useNavigationLayoutContext();
  const currentPath = location.pathname;
  const isConnectedMobileHome = currentPath == ONE_NAV_MOBILE_PATH;

  const getBottomNavItems = () => {
    let isAnyActive = false;
    return BOTTOM_NAV_ITEMS.map(({ title, href, icon }) => {
      const isActive = href
        ? Boolean(matchPath({ path: href, end: false }, location.pathname))
        : false;

      if (isActive) {
        isAnyActive = true;
      }

      if (title === 'More') {
        return (
          <BottomNavItem
            key={title}
            title={title}
            icon={icon}
            isActive={!isAnyActive}
            onClick={() => {
              setIsSideNavOpenOnMobile(true);
            }}
          />
        );
      }
      return (
        <BottomNavItem
          key={title}
          title={title}
          href={href}
          icon={icon}
          isActive={isActive}
          as={Link}
        />
      );
    });
  };

  if (!isMobile) {
    return null;
  }

  if (isConnectedMobileHome) {
    return null;
  }

  return <BottomNav>{getBottomNavItems()}</BottomNav>;
};

export default BottomNavigation;
