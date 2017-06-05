import ajax from 'merchant/utils/ajax';

export default class User {
  constructor(props) {
    Object.assign(this, props);
  }

  fetch() {
    const Klass = this.constructor;
    return ajax({
      url: '/user',
      appendModeInURL: false,
    }).then(response => {
      response.data = new Klass(response.data);
      return response;
    });
  }

  get isAuthenticated() {
    return !!this.current;
  }

  get isVerified() {
    return this.user.confirmed;
  }

  get isPreSignupDone() {
    if (
      !this.user.merchants.length ||
      this.pre_signup.length === 0 ||
      this.created_at < 1488306600 // pre-signup is only for signup on/after 01 March 2017
    ) {
      return true;
    }

    return (
      this.pre_signup &&
      Object.keys(this.pre_signup)
        // get all values
        .map(key => {
          return this.pre_signup[key];
        })
        // reduce all values using '&&'
        .reduce((x, y) => {
          return x && y;
        })
    );
  }

  get isActivated() {
    return !!parseInt(this.user.activated);
  }

  get isNewUIEnabled() {
    return (this.tags || []).indexOf('Newui') !== -1;
  }
}
