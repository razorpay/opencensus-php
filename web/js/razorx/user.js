import { observable } from 'mobx';

const user = observable.shallowBox(window.user).get();

export default user;
export const org = observable.shallowBox(window.org).get();

export function isSuperAdmin() {
  const roles = user.roles || [];

  const isPresent = roles.some((element) => {
    return element.toLowerCase().match('superadmin');
  });

  if (isPresent) {
    return true;
  }

  return null;
}

export function isRzpApprover() {
  const roles = user.roles;

  const isPresent = roles.some((element) => {
    return (
      element?.toLowerCase()?.match('splitz_admin') ||
      element?.toLowerCase()?.match('razorx_approvers')
    );
  });

  return isPresent;
}

export function isOrgRazorpay() {
  return org.custom_code === 'rzp';
}

export function getOrgId() {
  return org.id.replace('org_', '');
}
