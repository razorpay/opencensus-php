import ajax from 'merchant/utils/ajax';
import { filterBy } from 'rzp/utils/rzp-utils';

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
    }).then(response => {
      response.data = new User(response.data);
      return response;
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

  get isSubmitted() {
    return !!parseInt(this.submitted);
  }

  get isOldUIEnabled() {
    return (this.tags || []).indexOf('Oldui') !== -1;
  }

  get isMarketplaceEnabled() {
    return this.isFeatureEnabled('marketplace');
  }

  get isVirtualAccountsEnabled() {
    return this.isFeatureEnabled('virtual_accounts');
  }

  get isSubscriptionsEnabled() {
    return this.isFeatureEnabled('virtual_accounts');
  }

  get isGSTDisabled() {
    return (this.tags || []).indexOf('Gst_Invoice_Disabled') !== -1;
  }

  setFeatures(features) {
    let enabledFeatures = filterBy(features, 'value', true);

    this.features = enabledFeatures;
  }

  get enabledFeatures() {
    let pluckKey = 'feature';

    return (this.features || []).map(object => {
      return object[pluckKey];
    });
  }
}
