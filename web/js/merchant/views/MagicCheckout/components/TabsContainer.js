import { useCallback } from 'react';
import { NavLink, Redirect, Switch } from 'react-router-dom';
import Spinner from 'common/ui/Spinner';
import magicCheckoutRoutes from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';

let redirectPath;
const RouteContainer = ({ user, isCODIntelligenceEnabled }) => {
  const renderNav = useCallback(
    (item) => {
      if (item.tabName === 'RTO Analytics' && !isCODIntelligenceEnabled) return null;
      if (item.condition && !item.condition(user)) return null;
      if (!redirectPath) {
        redirectPath = item.path;
      }
      return (
        <NavLink key={item.path} to={item.path} exact>
          {item.tabName}
        </NavLink>
      );
    },
    [redirectPath, user, isCODIntelligenceEnabled],
  );
  return (
    <tabbed-container>
      {isCODIntelligenceEnabled === null ? (
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
                  exact
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
