import { Route, Switch, Redirect } from 'react-router-dom';

import { ShowWhenRoute } from 'merchant/components/ShowWhen';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Earnings from './Commissions';
import Applications from './Applications';

export default function PartnerDashboard() {
  return (
    <Switch>
      <Redirect to="/partners/submerchants" from="/partners" exact />
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

      <ShowWhenRoute path="/partners/earnings" component={Earnings} />

      <Route path="/partners/submerchants" component={SubMerchantList} />
    </Switch>
  );
}
