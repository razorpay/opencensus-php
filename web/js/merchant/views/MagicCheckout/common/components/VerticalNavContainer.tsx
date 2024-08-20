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
  RoutesConfig,
  RouteItem,
} from 'merchant/views/MagicCheckout/types';

interface NestedVerticalTabProps {
  PATH_PREFIX: string;
  NAV_ITEMS: RoutesConfig;
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
  PATH_PREFIX,
  NAV_ITEMS,
}) => {
  const { platform, showTabHeading } = settings;
  const { rcod: isRCOD } = magicCheckout;

  const { abExperiments } = useSplitzService();

  let redirectPath;

  return (
    <SuspenseWithLoader type="center">
      <StyledTabsWrapper>
        <div className="magic-settings-tabs display-flex">
          <div className="tabs-container display-flex flex--column">
            {NAV_ITEMS[platform as Platform].map((item, index) => {
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
            {NAV_ITEMS[platform as Platform].map((item) => {
              if (!isRouteAuthorised(item, user, abExperiments, isRCOD as boolean)) return null;
              if (!customRouteCheck(item, user)) return null;
              return (
                <Route
                  path={`${item.path.replace(PATH_PREFIX, '')}/*`}
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
