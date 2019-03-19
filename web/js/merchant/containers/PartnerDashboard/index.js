import { Route, Switch } from 'react-router-dom';

import { ShowWhenRoute } from 'merchant/components/ShowWhen';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Commissions from './Commissions/List';
import Applications from './Applications';

export default function PartnerDashboard() {
  return (
    <Switch>
      <ShowWhenRoute
        additionalCondition={user =>
          user.isPartner('aggregator', 'fully_managed')
        }
        path="/partners/settings"
        component={Settings}
      />

      <ShowWhenRoute
        additionalCondition={user => user.isPartner('pure_platform')}
        path="/partners/applications"
        component={Applications}
      />

      <ShowWhenRoute
        additionalCondition={user => !user.isPartner('resller')}
        path="/partners/earnings"
        component={Commissions}
      />

      <Route path="/partners/submerchants" component={SubMerchantList} />
    </Switch>
  );
}
