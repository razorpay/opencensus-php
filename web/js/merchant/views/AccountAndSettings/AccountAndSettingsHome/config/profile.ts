import { agentRole, RBLRoles, RegistrationLinkRoles, roles } from 'merchant/helpers/data';
import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  CHANGE_PASSWORD,
  UPDATE_CONTACT_NUMBER,
  UPDATE_DISPLAY_NAME,
  UPDATE_LOGIN_EMAIL,
} from 'merchant/views/Account/Profile/deeplink-constants';
import {
  PersonalProfileFields,
  StoredInfoDataInterface,
  User,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

const Roles = { ...roles, ...agentRole, ...RBLRoles, ...RegistrationLinkRoles };

export const getRole = ({ user }) => {
  const role = user.userRole;
  return Roles[role]?.label;
};

export const DisplayNameConfig: StoredInfoDataInterface = {
  id: PersonalProfileFields.DISPLAY_NAME,
  displayName: 'Display name',
  tooltip: {
    description:
      'This is the display name that you and your team will see on the Razorpay dashboard',
  },
  value: '',
  isEditEnable: false,
  queryParam: UPDATE_DISPLAY_NAME,
  getValue: ({ user: { display_name } }: User): string => display_name || '--',
  isVisible: ({ isRevampedInfo }): boolean => !isRevampedInfo,
  shouldEdit: ({ user }: User): boolean => user.isAdminOrOwner,
  selfServeActionName: 'Display Name Updated',
  analyticsEventInfo: {
    objectName: 'dispay name edit',
    actionName: 'clicked',
  },
};

export const ContactNumberConfig: StoredInfoDataInterface = {
  id: PersonalProfileFields.CONTACT_MOBILE,
  displayName: 'Phone number',
  queryParam: UPDATE_CONTACT_NUMBER,
  value: '',
  isEditEnable: false,
  getValue: ({
    user: {
      user: { contact_mobile },
    },
  }: User): string => contact_mobile || '--',
  isVisible: (): boolean => true,
  shouldEdit: ({ user }: User): boolean => user.isContactMobileChangeAllowed,
  selfServeActionName: 'Mobile Updated',
  analyticsEventInfo: {
    objectName: 'change contact number',
    actionName: 'clicked',
  },
};

export const LoginEmailConfig: StoredInfoDataInterface = {
  id: PersonalProfileFields.EMAIL,
  displayName: 'Login email',
  queryParam: UPDATE_LOGIN_EMAIL,
  value: '',
  isEditEnable: false,
  getValue: ({
    user: {
      user: { email },
    },
  }: User): string => email || '--',
  isVisible: ({ user: { user, userRole } }: User): boolean => {
    return userRole === rolesList.OWNER || Object.keys(user.merchants).length > 1;
  },
  shouldEdit: ({
    user: {
      user: { email, signup_via_email },
      userRole,
      isEmailSelfServeEnabled,
    },
  }: User): boolean =>
    email
      ? isEmailSelfServeEnabled && userRole === rolesList.OWNER
      : userRole === rolesList.OWNER && !signup_via_email,
  isCriticalFlowEnabled: true,
  selfServeActionName: 'Login Details Updated',
  analyticsEventInfo: {
    objectName: 'Edit email',
    actionName: 'clicked',
  },
  editTooltip: {
    description:
      'You’re added as a team member in this account. To update your email, contact your account owner to invite you using the new email.\n If you’re an owner of another Razorpay account, switch to that Merchant ID to update your email',
  },
};

export const PasswordConfig: StoredInfoDataInterface = {
  id: PersonalProfileFields.PASSWORD,
  displayName: 'Password',
  queryParam: CHANGE_PASSWORD,
  value: '',
  isEditEnable: false,
  getValue: (): string => '•••••••••••',
  isVisible: ({ user: { user }, profile }: User): boolean =>
    user?.signup_via_email || profile?.check_password?.data?.set_password,
  shouldEdit: (): boolean => true,
  selfServeActionName: 'Password Updated',
  analyticsEventInfo: {
    objectName: 'change password',
    actionName: 'clicked',
  },
};

export const UserNameConfig = {
  id: PersonalProfileFields.NAME,
  displayName: 'Name',
  value: '',
  isEditEnable: false,
  getValue: ({
    user: {
      user: { name },
    },
  }: User): string => name,
  isVisible: (): boolean => true,
  shouldEdit: (): boolean => false,
  selfServeActionName: 'User Name Updated',
  analyticsEventInfo: {
    objectName: 'change username',
    actionName: 'clicked',
  },
};

export const InfoDataConfig: StoredInfoDataInterface[] = [
  DisplayNameConfig,
  ContactNumberConfig,
  LoginEmailConfig,
  PasswordConfig,
];
