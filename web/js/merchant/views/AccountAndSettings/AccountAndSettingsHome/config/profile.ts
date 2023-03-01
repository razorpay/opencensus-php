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

export const InfoDataConfig: StoredInfoDataInterface[] = [
  {
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
    isVisible: (): boolean => true,
    shouldEdit: ({ user }: User): boolean => user.isAdminOrOwner,
    selfServeActionName: 'Display Name Updated',
    analyticsEventInfo: {
      objectName: 'dispay name edit',
      actionName: 'clicked',
    },
  },
  {
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
  },
  {
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
  },
  {
    id: PersonalProfileFields.PASSWORD,
    displayName: 'Password',
    queryParam: CHANGE_PASSWORD,
    value: '',
    isEditEnable: false,
    getValue: (): string => '*********',
    isVisible: ({ user: { user }, profile }: User): boolean =>
      user?.signup_via_email || profile?.check_password?.data?.set_password,
    shouldEdit: (): boolean => true,
    selfServeActionName: 'Password Updated',
    analyticsEventInfo: {
      objectName: 'change password',
      actionName: 'clicked',
    },
  },
];
