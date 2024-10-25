import React from 'react';
import { NavLink, Navigate, Routes, Route } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { updatePageView } from 'merchant/reducers/magicCheckout/magicSettings/actions';

import { useSplitzService } from 'common/splitz';
import { isRouteAuthorised } from 'merchant/views/MagicCheckout/utils/genericRouteCheck';

import { RouteGuard } from 'merchant/components/ShowWhen';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NestedVerticalTabItem from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTabItem';
import { StyledTabsWrapper } from 'merchant/views/MagicCheckout/Settings/containers/styledComponents';

import {
  Platform,
  User,
  GenericRecord,
  PlatformSpecificRoutes,
  RouteItem,
} from 'merchant/views/MagicCheckout/types';
import {
  checkMagicConfigurationFlow,
  convertPlatformRoutesToConfigurationFlow,
} from 'merchant/views/MagicCheckout/utils/Configuration';

interface NestedVerticalTabProps {
  basePath: string;
  navItems: PlatformSpecificRoutes;
  customRouteCheck?: (item: RouteItem, user: User) => boolean;
  settings: GenericRecord;
  magicCheckout: GenericRecord;
  user: User;
}

/**
 * Common Component to render all L2 Navigations(vertical Navbar) and its respective
 * content in a container as part of magic dashboard revamp
 */
const NestedVerticalTab: React.FC<NestedVerticalTabProps> = ({
  settings,
  magicCheckout,
  customRouteCheck = () => true,
  user,
  basePath,
  navItems,
}) => {
  const { platform, showTabHeading } = settings;
  const { rcod: isRCOD } = magicCheckout;

  const { abExperiments } = useSplitzService();

  let redirectPath;
  const pathPrefix = checkMagicConfigurationFlow() ? `/configuration${basePath}` : basePath;
  const routes = checkMagicConfigurationFlow()
    ? convertPlatformRoutesToConfigurationFlow(navItems)
    : navItems;

  return (
    <SuspenseWithLoader type="center">
      <StyledTabsWrapper>
        <div className="magic-settings-tabs display-flex" style={{ margin: 0 }}>
          <div className="tabs-container display-flex flex--column">
            {routes?.[platform as Platform]?.map((item, index) => {
              if (!customRouteCheck(item, user)) return null;

              if (!isRouteAuthorised(item, user, abExperiments, isRCOD as boolean)) return null;

              if (!redirectPath) {
                redirectPath = item.path;
              }
              return (
                <NavLink
                  to={item.path}
                  className="tabs-items pointer padding-16 font-bold"
                  key={`${item.label}_${index}`}
                >
                  {item.label}
                </NavLink>
              );
            })}
          </div>
          <Routes>
            {routes?.[platform as Platform]?.map((item) => {
              if (!isRouteAuthorised(item, user, abExperiments, isRCOD as boolean)) return null;
              if (!customRouteCheck(item, user)) return null;

              return (
                <Route
                  path={`${item.path.replace(pathPrefix, '')}/*`}
                  key={item.path}
                  element={
                    <RouteGuard>
                      <NestedVerticalTabItem
                        tabContent={item.Component}
                        tabHeading={item.tabHeading}
                        showTabHeading={showTabHeading as boolean}
                        className={item.className}
                        abExperiments={abExperiments}
                      />
                    </RouteGuard>
                  }
                />
              );
            })}
            <Route path="*" element={<Navigate to={redirectPath} replace />} />
          </Routes>
        </div>
      </StyledTabsWrapper>
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  magicCheckout: state.magicCheckout,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updatePage: updatePageView,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(NestedVerticalTab);
