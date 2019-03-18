import { Route, Switch, NavLink } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Applications from 'merchant/containers/Applications';
import ApplicationEntity from 'merchant/containers/Applications/new';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Commissions from './Commissions/List';

export default function PartnerDashboard() {
  return (
    <tabbed-container>
      <header id="partner-header">
        <NavLink exact to="/submerchants">
          Affiliated Accounts
        </NavLink>
        <ShowWhen
          myRole="owner manager admin"
          additionalCondition={user =>
            user.isPartner('aggregator', 'fully_managed')
          }
        >
          <NavLink to="/submerchants/settings">Settings</NavLink>
        </ShowWhen>
        <ShowWhen
          myRole="owner manager admin"
          additionalCondition={user => user.isPartner('pure_platform')}
        >
          <NavLink to="/submerchants/applications">Applications</NavLink>
        </ShowWhen>

        <ShowWhen
          additionalCondition={user => !user.isPartner('reseller')}
          featureEnabled="show_commissions"
        >
          <NavLink to="/commissions">Transactional Details</NavLink>
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

          <ShowWhenRoute
            additionalCondition={user => user.isPartner('pure_platform')}
            path="/submerchants/applications/new"
            component={ApplicationEntity}
          />

          <ShowWhenRoute
            additionalCondition={user => user.isPartner('pure_platform')}
            path="/submerchants/applications/:id"
            component={ApplicationEntity}
          />

          <ShowWhenRoute
            additionalCondition={user => user.isPartner('pure_platform')}
            path="/submerchants/applications"
            component={Applications}
          />

          <ShowWhenRoute
            additionalCondition={user => !user.isPartner('resller')}
            path="/commissions"
            component={Commissions}
          />

          <Route path="/submerchants" component={SubMerchantList} />
        </Switch>
      </content>
    </tabbed-container>
  );
}
