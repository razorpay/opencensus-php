import React, { useEffect, useState } from 'react';
import { Link, Route, Switch } from 'react-router-dom';
import { compose } from 'redux';
import { connect } from 'react-redux';

import {
  getBannerAndModalVisibility,
  fetchMerchantWebsiteDetails,
} from 'merchant/reducers/websitecompliance';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { fetchPayments } from 'merchant/reducers/collection';
import Announcement from 'merchant/components/Announcements/Instant';

import OnboardingCard from 'merchant/views/onboarding/mobile/Screens/Home';
import WebsiteComplianceNudge from 'merchant/views/Account/WebsiteAppDetails/Nudge';
import WebsiteComplianceBanner from 'merchant/components/Announcements/WebsiteCompliance';
import KeysAndPlugins from './KeysAndPlugins';

const ApiKeysAndPlugins = ({
  // state from redux
  user,
  mode,
  referee,
  limitBreach,
  // actions from redux
  fetchPayments,
  getBannerAndModalVisibility,
  fetchMerchantWebsiteDetails,
}): JSX.Element => {
  const [payments, setPayments] = useState<any>(null);

  useEffect(() => {
    fetchPayments().then((res) => {
      setPayments({ items: res?.data?.items ?? [] });
    });

    if (user.isWebsiteComplianceFlowEnabled) {
      getBannerAndModalVisibility();
      fetchMerchantWebsiteDetails();
    }
  }, []);

  return (
    <div className="keys-plugins-page-wrapper product-led-onboarding">
      <div className="hidden-sm">
        {user.showInstantActivation && (
          <Announcement
            className="hidden-sm"
            mode={mode}
            user={user}
            payments={payments}
            limitBreach={limitBreach}
          />
        )}
        <WebsiteComplianceBanner className="hidden-sm" screen="API Keys & Plugins" />
      </div>
      <div className="display-sm">
        {user.isOnboardingV2Enabled ? <OnboardingCard referee={referee} /> : null}
        <WebsiteComplianceNudge screen="API Keys & Plugins" />
      </div>
      <div className="tabbed-container">
        <header className="scrollable-tab-header hidden-sm">
          <Link className="active" to="/api-keys">
            API Keys & Plugins
          </Link>
          <div className="btn-toolbar pull-right" />
        </header>
        <div className="content">
          <ErrorBoundary resetOnProps>
            <Switch>
              <Route path="/api-keys" component={KeysAndPlugins} />
            </Switch>
          </ErrorBoundary>
        </div>
      </div>
    </div>
  );
};

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      limitBreach: state.home.limitBreach,
      referee: state.merchantReferral.data.referee,
    }),
    {
      fetchPayments,
      getBannerAndModalVisibility,
      fetchMerchantWebsiteDetails,
    },
  ),
)(ApiKeysAndPlugins);
