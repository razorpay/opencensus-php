import ajax from 'merchant/utils/ajax';
import { filterBy } from 'rzp/utils/rzp-utils';

import { fetchFeaturesAjax } from 'merchant/modules/config';

// TODO: Rename fn. name
export function setFeatures(features) {
  let enabledFeatures = filterBy(features, 'value', true);

  return enabledFeatures;
}

export default class User {
  merchants = {};

  constructor(props) {
    Object.assign(this, props);
  }

  isFeatureEnabled(feature) {
    return (this.enabledFeatures || []).indexOf(feature.toLowerCase()) !== -1;
  }

  fetch() {
    let promise = new Promise((resolve, reject) => {
      ajax({
        url: '/user',
        appendModeInURL: false,
      })
        .then(response => {
          // Risky. fetchFeaturesAjax can make the request always in 'test'mode.
          // But hopefully, it will happen after cycle of App.js fetch User where it updatesSession with correct mode
          fetchFeaturesAjax(response.data.current)
            .catch(_ => _)
            .then(data => {
              let newUser = new User(response.data);
              newUser.features = setFeatures(
                data.success ? data.data.features : []
              );
              response.data = newUser;
              resolve(response);
            })
            .catch(err => {
              reject(err);
            });
        })
        .catch(err => reject(err));
    });

    return promise;
  }

  get userRole() {
    if (this.current && Object.keys(this.merchants).length) {
      return this.merchants[this.current].role;
    }
    return null;
  }

  get isAuthenticated() {
    return !!this.user;
  }

  get isVerified() {
    return this.user.confirmed;
  }

  get isActivated() {
    return !!parseInt(this.activated);
  }

  get needsClarification() {
    return this.activation_status === 'needs_clarification';
  }

  get isSubmitted() {
    return !!parseInt(this.submitted);
  }

  get isRejected() {
    return this.activation_status === 'rejected';
  }

  get isMarketplaceEnabled() {
    return this.isFeatureEnabled('marketplace');
  }

  get isVirtualAccountsEnabled() {
    return this.isFeatureEnabled('virtual_accounts');
  }

  get isSubscriptionsEnabled() {
    return this.isFeatureEnabled('subscriptions');
  }

  get isGSTDisabled() {
    return (this.tags || []).indexOf('Gst_Invoice_Disabled') !== -1;
  }

  // TODO: Remove this code and reports v1 code when confirmed no rollbacks
  // Enabling reportsV2 for all merchants.
  get isReportV2Enabled() {
    return true;
    // return this.isFeatureEnabled('report_v2');
  }

  // TODO: Remove this code when confirmed no rollbacks
  get isNewAnalyticsEnabled() {
    return true;
  }

  get enabledFeatures() {
    let pluckKey = 'feature';

    return (this.features || []).map(object => {
      return object[pluckKey];
    });
  }

  get isOldBatchEnabled() {
    // TODO: temp fix for upper/lower case tags
    return (
      (this.tags.map(t => t.toLowerCase()) || []).indexOf(
        'batch_import_links'
      ) !== -1
    );
  }

  get isNewBatchEnabled() {
    // TODO: temp fix for upper/lower case tags
    return (
      (this.tags.map(t => t.toLowerCase()) || []).indexOf(
        'batch_import_links_v2'
      ) !== -1
    );
  }
}
