import { observable } from 'mobx';

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
