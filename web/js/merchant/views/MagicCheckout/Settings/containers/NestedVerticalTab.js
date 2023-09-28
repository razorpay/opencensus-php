import { NavLink, Navigate, Routes, Route } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { updatePageView } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { TABS } from 'merchant/views/MagicCheckout/Settings/constants';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { useSplitzService } from 'common/splitz';
import { StyledTabsWrapper } from 'merchant/views/MagicCheckout/Settings/containers/styledComponents';

export const TabItem = ({
  tabHeading,
  showTabHeading,
  tabContent: Component,
  className,
  abExperiments,
}) => {
  return (
    <div className={`tabs-content bg-white ${className}`}>
      {tabHeading && showTabHeading ? (
        <div className="padding-16 font-20 font-bold tab-heading">{tabHeading}</div>
      ) : null}
      <div className={`tabs-component${!tabHeading ? ' tab-padding' : ''}`}>
        <div className="tabs-component-wrapper">
          <Component abExperiments={abExperiments} />
        </div>
      </div>
    </div>
  );
};

const NestedVerticalTab = ({ settings, magicCheckout, user }) => {
  const { platform, showTabHeading } = settings;
  const { cod_order_control: isCODOrderControlEnabled } = magicCheckout;

  const { abExperiments } = useSplitzService();
  let redirectPath;
  return (
    <SuspenseWithLoader type="center">
      <StyledTabsWrapper>
        <div className="magic-settings-tabs display-flex">
          <div className="tabs-container display-flex flex--column">
            {TABS[platform].map((item) => {
              if (item.condition && !item.condition(user, abExperiments)) return null;
              if (
                item.label === 'COD Review Workflow' &&
                (!isCODOrderControlEnabled || !user.isMagicCODOrderAutomationEnabled)
              )
                return null;
              if (!redirectPath) {
                redirectPath = item.path;
              }
              return (
                <NavLink
                  end
                  to={item.path}
                  className="tabs-items pointer padding-16 font-bold"
                  key={item.label}
                >
                  {item.label}
                </NavLink>
              );
            })}
          </div>
          <Routes>
            {TABS[platform].map((item) => {
              if (item.condition && !item.condition(user, abExperiments)) return null;
              if (item.label === 'COD Review Workflow' && !isCODOrderControlEnabled) return null;
              const isIndex = item.path === '/magic/settings';
              return (
                <Route
                  path={isIndex ? '' : `${item.path.replace('/magic/settings/', '')}/*`}
                  key={item.path}
                  index={isIndex}
                  element={
                    <RouteGuard>
                      <TabItem
                        tabContent={item.Component}
                        tabHeading={item.tabHeading}
                        showTabHeading={showTabHeading}
                        className={item.className}
                        abExperiments={abExperiments}
                      />
                    </RouteGuard>
                  }
                />
              );
            })}
            <Route index element={<Navigate to={redirectPath} replace />} />
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
