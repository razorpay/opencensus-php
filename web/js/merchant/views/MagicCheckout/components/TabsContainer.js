import { NavLink, Redirect } from 'react-router-dom';
import magicCheckoutRoutes from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { useCallback } from 'react';

let redirectPath;
const RouteContainer = ({ user }) => {
  const renderNav = useCallback(
    (item) => {
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
    [redirectPath, user],
  );
  return (
    <tabbed-container>
      <header id="super-checkout-header" className="scrollable-tab-header">
        {magicCheckoutRoutes.map(renderNav)}
      </header>
      <content>
        <div className="content-wrapper">
          {magicCheckoutRoutes.map((item) => (
            <ShowWhenRoute
              path={item.path}
              key={item.path}
              exact
              component={item.Component}
              additionalCondition={(_user) => !item.condition || item.condition(_user)}
            />
          ))}
        </div>
      </content>
      <Redirect to={redirectPath} />
    </tabbed-container>
  );
};

export default RouteContainer;
