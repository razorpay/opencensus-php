import { useCallback } from 'react';
import { NavLink, Redirect, Switch } from 'react-router-dom';
import Spinner from 'common/ui/Spinner';
import magicCheckoutRoutes from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { ACCESS_ROLES } from 'merchant/views/MagicCheckout/Settings/constants';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

let redirectPath;
const RouteContainer = ({
  user,
  isCODIntelligenceEnabled,
  isCODOrderControlEnabled,
  isPrepayCODEnabled,
  platform,
}) => {
  const renderNav = useCallback(
    (item) => {
      if (
        item.tabName === 'RTO Analytics' &&
        !isCODIntelligenceEnabled &&
        (!user.isMagicRTOAnalyticsV3Enabled || !isCODOrderControlEnabled)
      )
        return null;
      if (item.tabName === 'COD Orders' && !isCODOrderControlEnabled) return null;
      if (item.tabName === 'COD Order Conversion' && (platform === 'native' || !isPrepayCODEnabled))
        return null;
      if (item.condition && !item.condition(user)) return null;
      if (item.tabName === 'Edit Orders' && platform !== PLATFORMS.VALUES.SHOPIFY) return null;
      if (
        item.tabName === 'Settings' &&
        !ACCESS_ROLES.includes(user.role) &&
        !isCODOrderControlEnabled
      )
        return null;
      if (!redirectPath) {
        redirectPath = item.path;
      }
      return (
        <NavLink key={item.path} to={item.path}>
          {item.tabName}
        </NavLink>
      );
    },
    [redirectPath, user, isCODIntelligenceEnabled, isCODOrderControlEnabled, platform],
  );
  return (
    <tabbed-container>
      {isCODIntelligenceEnabled === null || isCODOrderControlEnabled === null ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <>
          <header id="super-checkout-header" className="scrollable-tab-header">
            {magicCheckoutRoutes.map(renderNav)}
          </header>
          <content>
            <Switch>
              {magicCheckoutRoutes.map((item) => (
                <ShowWhenRoute
                  path={item.path}
                  key={item.path}
                  component={item.Component}
                  additionalCondition={(_user) => !item.condition || item.condition(_user)}
                />
              ))}
              <Redirect to={redirectPath} />
            </Switch>
          </content>
        </>
      )}
    </tabbed-container>
  );
};
export default RouteContainer;
