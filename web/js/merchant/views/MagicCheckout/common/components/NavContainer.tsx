import React from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { RouteGuard } from 'merchant/components/ShowWhen';

import { useSplitzService } from 'common/splitz';

import { isRouteAuthorised } from 'merchant/views/MagicCheckout/utils/genericRouteCheck';

import { RoutesConfig, Platform, RouteItem, User } from 'merchant/views/MagicCheckout/types';

interface NavContainerProps {
  routes: RoutesConfig;
  path: string;
  handleNavClick?: (item: RouteItem) => unknown;
  user: User;
  platform: Platform;
  isRCOD: boolean;
}

/**
 * Common Component to render all L3 Navigations(Horizantal Navbar) and its respective
 * content in a container as part of magic dashboard revamp
 */
const NavContainer: React.FC<NavContainerProps> = (props) => {
  let redirectPath = '';
  const { abExperiments } = useSplitzService();
  const { routes: ROUTES, path, handleNavClick } = props; //props from parent
  const { user, platform, isRCOD } = props; // props from store

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
        onClick={() => handleNavClick && handleNavClick(item)}
      >
        {item.label}
      </NavLink>
    );
  };

  return (
    <SuspenseWithLoader type="center">
      <div className="tabbed-container" style={{ width: '100%' }}>
        <header id="super-checkout-header" className="scrollable-tab-header">
          {ROUTES?.[platform]?.map(renderNav)}
        </header>
        <div className="content ">
          <Routes>
            {ROUTES?.[platform]?.map((item: RouteItem) => {
              return (
                <Route
                  key={item.path}
                  path={`${item.path.replace(path, '')}/*`}
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
  platform: state?.magicCheckout?.platform,
  isRCOD: state?.magicCheckout?.rcod,
});

export default connect(mapStateToProps, null)(NavContainer);
