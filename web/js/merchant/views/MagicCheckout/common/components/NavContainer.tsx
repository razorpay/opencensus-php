import React from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';
import { connect } from 'react-redux';

import { Box } from '@razorpay/blade/components';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { RouteGuard } from 'merchant/components/ShowWhen';

import { useSplitzService } from 'common/splitz';

import { isRouteAuthorised } from 'merchant/views/MagicCheckout/utils/genericRouteCheck';
import {
  checkMagicConfigurationFlow,
  convertPlatformRoutesToConfigurationFlow,
} from 'merchant/views/MagicCheckout/utils/Configuration';

import {
  PlatformSpecificRoutes,
  Platform,
  RouteItem,
  User,
} from 'merchant/views/MagicCheckout/types';

interface NavContainerProps {
  navItems: PlatformSpecificRoutes;
  basePath: string;
  handleNavClick?: (item: RouteItem) => unknown;
  user: User;
  platform: Platform;
  isRCOD: boolean;
  additionalStyles?: {
    container?: React.CSSProperties;
    header?: React.CSSProperties;
    content?: React.CSSProperties;
    tabItem?: React.CSSProperties;
  };
  children?: React.ReactNode;
}

/**
 * Common Component to render all L3 Navigations (Horizontal Navbar) and its respective
 * content in a container as part of magic dashboard revamp
 */
const NavContainer: React.FC<NavContainerProps> = (props) => {
  let redirectPath = '';
  const { abExperiments } = useSplitzService();
  const { navItems, basePath, handleNavClick, additionalStyles = {}, children } = props; // props from parent
  const { user, platform, isRCOD } = props; // props from store
  const pathPrefix = checkMagicConfigurationFlow() ? `/configuration${basePath}` : basePath;
  const routes = checkMagicConfigurationFlow()
    ? convertPlatformRoutesToConfigurationFlow(navItems)
    : navItems;

  const renderNav = (item: RouteItem) => {
    if (!isRouteAuthorised(item, user, abExperiments, isRCOD)) return false;
    if (!redirectPath) {
      redirectPath = item.path;
    }
    return (
      <NavLink
        end
        key={item.path}
        to={item.path}
        className="tabs-items pointer padding-8 font-bold"
        style={additionalStyles.tabItem}
        onClick={() => handleNavClick && handleNavClick(item)}
      >
        <Box display="flex" alignItems="center">
          {item.label}
          {item.renderNavItemTag ? item.renderNavItemTag() : null}
        </Box>
      </NavLink>
    );
  };

  return (
    <SuspenseWithLoader type="center">
      <div
        className="tabbed-container"
        style={{ width: '100%', backgroundColor: '#fff', ...additionalStyles.container }}
      >
        <header
          id="super-checkout-header"
          className="scrollable-tab-header"
          style={{
            ...additionalStyles.header,
          }}
        >
          {routes?.[platform]?.map(renderNav)}
          {children && <div style={{ marginLeft: 'auto' }}>{children}</div>}
        </header>
        <div className="content" style={additionalStyles.content}>
          <Routes>
            {routes?.[platform]?.map((item: RouteItem) => {
              return (
                <Route
                  key={item.path}
                  path={`${item.path.replace(pathPrefix, '')}/*`}
                  element={
                    <RouteGuard
                      additionalCondition={(_user) =>
                        isRouteAuthorised(item, user, abExperiments, isRCOD)
                      }
                    >
                      <item.Component />
                    </RouteGuard>
                  }
                />
              );
            })}
            <Route path="*" element={<Navigate to={redirectPath} replace />} />
          </Routes>
        </div>
      </div>
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  platform: state?.magic_settings?.platform,
  isRCOD: state?.magicCheckout?.rcod,
});

export default connect(mapStateToProps, null)(NavContainer);
