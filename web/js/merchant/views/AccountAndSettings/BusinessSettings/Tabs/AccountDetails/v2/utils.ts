import { User } from 'common/typings';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
import {
  ContactNumberConfig,
  DisplayNameConfig,
  LoginEmailConfig,
  UserNameConfig,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import {
  InfoDataInterface,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { getInfoData } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/utils/profile';
import {
  OWNER_ACCOUNT_DETAILS,
  USER_ACCOUNT_DETAILS,
} from 'merchant/views/AccountAndSettings/BusinessSettings/constants/constants';
import {
  InfoDetailsPageProps,
  OwnerDetailsInterface,
  QueryParamTriggerInterface,
  SubInfoDetails,
} from 'merchant/views/AccountAndSettings/BusinessSettings/typings';

type UserDetailsInterface = InfoDataInterface & SubInfoDetails;

const USER_DETAILS = [DisplayNameConfig, UserNameConfig, LoginEmailConfig, ContactNumberConfig];

const QUERY_PARAM_FIELDS = [
  PersonalProfileFields.DISPLAY_NAME,
  PersonalProfileFields.CONTACT_MOBILE,
  PersonalProfileFields.EMAIL,
];

export const getOwnerDetails = ({
  userRole,
  user,
}: {
  userRole: string;
  user: OwnerDetailsInterface;
}): InfoDetailsPageProps[] | null => {
  if (userRole.toLowerCase() !== rolesList.OWNER) {
    return OWNER_ACCOUNT_DETAILS.reduce((acc, details): InfoDetailsPageProps[] => {
      const { getValue, ...rest } = details;
      acc.push({
        ...rest,
        value: getValue(user),
        isEditEnable: false,
      });
      return acc;
    }, [] as InfoDetailsPageProps[]);
  }
  return null;
};

export const getUserAccountDetails = ({ user }: { user: User }): UserDetailsInterface[] => {
  const userDetails = getInfoData({ user, dataConfig: USER_DETAILS });
  return userDetails.reduce((acc, each): UserDetailsInterface[] => {
    acc.push({
      ...each,
      ...USER_ACCOUNT_DETAILS[each.id],
    });
    return acc;
  }, [] as UserDetailsInterface[]);
};

export const getQueryParamMapping = ({
  userDetails,
  handleEditAction,
}: Record<string, any>): QueryParamTriggerInterface[] => {
  return userDetails.reduce((acc, each): QueryParamTriggerInterface[] => {
    if (each.queryParam && QUERY_PARAM_FIELDS.includes(each.id)) {
      acc.push({
        key: ACTION_QUERY_PARAM_KEY,
        value: each.queryParam,
        trigger: handleEditAction.bind(null, each),
      });
    }
    return acc;
  }, [] as QueryParamTriggerInterface[]);
};

export const makeAnalytics = ({
  id,
  value,
  screen,
  selfServeActionName,
  analyticsEventInfo,
}: InfoDataInterface & { screen: string }): void => {
  const location = 'Account Details';
  selfServeTrackInitiate({
    selfServeAction: selfServeActionName,
    page: location,
    screen,
  });
  const { objectName, actionName, properties = {} } = analyticsEventInfo;
  const analyticsProperties: Record<string, unknown> = {
    location,
    ...properties,
  };
  if (id === PersonalProfileFields.DISPLAY_NAME && value) {
    // Add action=reset when user is editing has an existing display name
    analyticsProperties.action = 'reset';
  } else if (id === PersonalProfileFields.EMAIL) {
    // add email id in properties
    analyticsProperties.currentEmailId = value;
  }
  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen,
    properties: {
      ...analyticsProperties,
      version: 'v2',
    },
  });
};
