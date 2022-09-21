import ajax from 'merchantLA/utils/ajax';
import { filterBy } from 'common/utils/rzp-utils';

// TODO: Rename fn. name
export function setFeatures(features) {
  const enabledFeatures = filterBy(features, 'value', true);

  window.rzp_user = {
    ...window.rzp_user,
    features: enabledFeatures,
  };

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

  get isActivated() {
    // eslint-disable-next-line radix
    return !!parseInt(this.activated);
  }

  get isSubmitted() {
    // eslint-disable-next-line radix
    return !!parseInt(this.submitted);
  }
  // TODO: Remove this code when confirmed no rollbacks
  get isNewAnalyticsEnabled() {
    return true;
  }

  get enabledFeatures() {
    const pluckKey = 'feature';

    return (this.features || []).map((object) => {
      return object[pluckKey];
    });
  }

  get isAllowedLARefunds() {
    return this.isFeatureEnabled('allow_reversals_from_la');
  }

  get isShowParentPaymentIdEnabled() {
    return this.isFeatureEnabled('display_parent_payment_id');
  }

  /* Check if the tag exists */
  findTag(tag) {
    return !!this.tags.find((t) => t.toLowerCase() === tag.toLowerCase());
  }
}
