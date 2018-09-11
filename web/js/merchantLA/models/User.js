import ajax from 'merchantLA/utils/ajax';
import { filterBy } from 'rzp/utils/rzp-utils';

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
    return ajax({
      url: '/user',
      appendModeInURL: false,
    });
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

  /* Check if the tag exists */
  findTag(tag) {
    return !!this.tags.find(t => t.toLowerCase() === tag.toLowerCase());
  }
}
