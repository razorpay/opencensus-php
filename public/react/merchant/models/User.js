import ajax from 'merchant/utils/ajax';

export default class User {
  merchants = {};

  constructor(props) {
    Object.assign(this, props);
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
    return (this.tags || []).indexOf('Marketplace') !== -1;
  }

  get isGSTDisabled() {
    return (this.tags || []).indexOf('Gst_Invoice_Disabled') !== -1;
  }
}
