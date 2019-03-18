import { Route, Switch, NavLink } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Applications from 'merchant/containers/Applications';
import ApplicationEntity from 'merchant/containers/Applications/new';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Commissions from './Commissions/List';

export default function PartnerDashboard() {
  return (
    <Switch>
      <ShowWhenRoute
        additionalCondition={user =>
          user.isPartner('aggregator', 'fully_managed')
        }
        path="settings"
        component={Settings}
      />

      <ShowWhenRoute
        additionalCondition={user => user.isPartner('pure_platform')}
        path="applications/new"
        component={ApplicationEntity}
      />

      <ShowWhenRoute
        additionalCondition={user => user.isPartner('pure_platform')}
        path="applications/:id"
        component={ApplicationEntity}
      />

      <ShowWhenRoute
        additionalCondition={user => user.isPartner('pure_platform')}
        path="applications"
        component={Applications}
      />

      <ShowWhenRoute
        additionalCondition={user => !user.isPartner('resller')}
        path="earnings"
        component={Commissions}
      />

      <Route path="submerchants" component={SubMerchantList} />
    </Switch>
  );
}
