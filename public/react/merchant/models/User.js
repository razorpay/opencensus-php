import ajax from 'merchant/utils/ajax';

export default class User {
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

  get isAuthenticated() {
    return !!this.current;
  }

  get isVerified() {
    return this.user.confirmed;
  }

  get isActivated() {
    return !!parseInt(this.activated);
  }

  get isNewUIEnabled() {
    return (this.tags || []).indexOf('Newui') !== -1;
  }
}
