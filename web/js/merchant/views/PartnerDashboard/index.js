import { useEffect } from 'react';
import store from 'merchant/store';
import { Route, Switch } from 'react-router-dom';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { ShowWhenRoute as showWhenRoutex } from 'merchant_common/components/ShowWhen';
import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Earnings from './Earnings';
import Subvention from './Subvention';
import Applications from './Applications';
import PartnerReports from 'merchant_common/views/Reports/views/PartnerReports';
import Home from './Home';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import usePartnerPageNPS from 'merchant/views/PartnerDashboard/SubMerchant/utils/usePartnerPageNPS';
import useTrackPartnerExperiments from 'merchant/views/PartnerDashboard/SubMerchant/utils/useTrackPartnerExperiments';
import Configuration, { AppConfiguration } from './Settings/configuration';
const PartnerShowWhenRoute = showWhenRoutex(store, '/partners/submerchants');

export default function PartnerDashboard() {
  const user = store.getState().session.user;
  const isPartnershipFUX = user?.isPartnershipFUX || false;

  usePartnerPageNPS('mdYeQMQH');
  useTrackPartnerExperiments(user);
  useEffect(() => {
    if (isPartnershipFUX) {
      document.body.style.backgroundColor = '#eaedff';
    }
    return () => {
      if (isPartnershipFUX) {
        document.body.style.backgroundColor = null;
      }
    };
  }, [isPartnershipFUX]);
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} resetOnProps>
      <Switch>
        <PartnerShowWhenRoute
          additionalCondition={(user) => user.isPartner() && user.isPartnershipFUX}
          path="/partners"
          component={Home}
          exact
        />
        <ShowWhenRoute
          additionalCondition={(user) => user.isPartner('aggregator', 'fully_managed')}
          path="/partners/settings"
          component={Settings}
        />

        <ShowWhenRoute
          additionalCondition={(user) =>
            user.isPartner('aggregator', 'fully_managed') && user.isPartnershipForPhantomEnabled
          }
          path="/partners/config"
          component={Configuration}
        />
        <ShowWhenRoute
          additionalCondition={(user) => user.isPartner('pure_platform')}
          path="/partners/applications/configuration/:id"
          component={AppConfiguration}
        />

        <ShowWhenRoute
          additionalCondition={(user) => user.isPartner('pure_platform')}
          path="/partners/applications"
          component={Applications}
        />

        <ShowWhenRoute
          path="/partners/earnings"
          component={Earnings}
          additionalCondition={(user) =>
            user.isAllowedView('earnings') && user.isHavingPartnerConfigs
          }
        />

        <ShowWhenRoute
          path="/partners/subventions"
          component={Subvention}
          additionalCondition={(user) =>
            user.isAllowedView('earnings') && user.isHavingSubventionConfigs
          }
        />

        <ShowWhenRoute
          path="/partners/reports"
          component={PartnerReports}
          // disabling for resellers not having partner configs
          additionalCondition={(user) => !user.isPartner('reseller') || user.isHavingPartnerConfigs}
        />

        <Route path="/partners/submerchants" component={SubMerchantList} />
      </Switch>
    </ErrorBoundary>
  );
}
