import { Route, Switch, NavLink } from 'react-router-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import { ShowWhenRoute } from 'merchant/components/Content';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';

export default function PartnerDashboard() {
  return (
    <tabbed-container>
      <header id="partner-header">
        <NavLink exact to="/submerchants">
          Affiliated Accounts
        </NavLink>
        <ShowWhen
          additionalCondition={user =>
            user.isPartner('aggregator', 'fully_managed')
          }
        >
          <NavLink to="/submerchants/settings">Settings</NavLink>
        </ShowWhen>
      </header>
      <content>
        <Switch>
          <ShowWhenRoute
            additionalCondition={user =>
              user.isPartner('aggregator', 'fully_managed')
            }
            path="/submerchants/settings"
            component={Settings}
          />
          <Route path="/submerchants" component={SubMerchantList} />
        </Switch>
      </content>
    </tabbed-container>
  );
}
