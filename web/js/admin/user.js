import { observable, action } from 'mobx';

const user = observable.shallowBox(window.user).get();

export default user;
export const org = observable.shallowBox(window.org).get();

export function isSuperAdmin() {
  let roles = user.roles || [];

  var isPresent = roles.some(function(element) {
    return element.toLowerCase().match('superadmin');
  });

  if (isPresent) {
    return true;
  }
}

export function isOrgHDFC() {
  return org.custom_code === 'hdfc';
}

export function isOrgRazorpay() {
  return org.custom_code === 'rzp';
}

export const AppStore = observable
  .box({
    appMode: 'live',

    get mode() {
      return this.appMode;
    },

    updateMode: function(e) {
      AppStore.appMode = e.target.value;
    },
  })
  .get();
