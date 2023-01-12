import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import { Heading, Text, Link } from '@razorpay/blade/components';
import ProfilePhoto from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/ProfilePhoto';
import UserInfo from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/UserInfo';
import MerchantDetails from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/MerchantDetails';
import Verification from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Verification';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import {
  ProfileContainer,
  ProfileContent,
  UserProfile,
  Details,
  Subheading,
  MobileProfileContainer,
  MobileProfileView,
  ProfileDetail,
} from './styled';
import Collapsible from 'common/components/Collapsible';
import * as ModalActions from 'merchant_common/reducers/modals';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getInfoData } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/utils/profile';
import { titleCase } from 'common/utils/rzp-utils';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
import { updateSession } from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ProfileActions from 'merchant/reducers/profile';
import { updateUser } from 'merchant_common/reducers/user';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { FORM_MAP } from './handlers';
import {
  ProfilePropsInterface,
  InfoDataInterface,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';

const makeAnalyticsCall = ({
  id,
  selfServeActionName,
  analyticsEventInfo,
  value,
  screen,
}: Pick<InfoDataInterface, 'id' | 'analyticsEventInfo' | 'selfServeActionName' | 'value'> & {
  screen: string;
}) => {
  const location = 'Personal Profile';

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
    properties: analyticsProperties,
  });
};

const Profile = (props: ProfilePropsInterface): JSX.Element => {
  const { isMobile, openModal, user, profile } = props;
  const { id: merchantId, contact_name: merchantName, logo_url: imageUrl } = user;
  const [isShowMore, setIsShowMore] = useState<boolean>(false);
  const infoData = getInfoData({ user, profile });

  const context = useTwoFactorVerificationContext();

  const handleEditClick = ({
    id,
    queryParam,
    handlerType,
    isCriticalFlowEnabled,
    selfServeActionName,
    analyticsEventInfo,
    value,
  }: InfoDataInterface): void => {
    const { attributes = {}, Component: FormComponent } = FORM_MAP[id]?.({
      props,
      id,
      handlerType,
    });
    const screen = Modules.AccountAndSettings;

    const modalConfig = {
      size: 'small',
      component: <FormComponent {...attributes} screen={screen} isNewAccountAndSettingsPage />,
      ...(queryParam
        ? {
            queryParams: {
              [ACTION_QUERY_PARAM_KEY]: queryParam,
            },
          }
        : {}),
    };

    makeAnalyticsCall({
      id,
      selfServeActionName,
      analyticsEventInfo,
      value,
      screen,
    });

    if (isCriticalFlowEnabled) {
      context.criticalFlow({
        modes: ['test', 'live'],
        onUserTwoFaVerified: () => {
          openModal(modalConfig);
        },
        onFlowTermination: () => {},
        isNewAccountAndSettingsPage: true,
      });
    } else {
      openModal(modalConfig);
    }
  };

  return isMobile ? (
    <MobileProfileContainer>
      <MobileProfileView isOpen={isShowMore}>
        <ProfileDetail>
          <ProfilePhoto imageUrl={imageUrl} />
          <Details>
            <Heading size="small">{titleCase(merchantName)}</Heading>
            <Text type="subdued" size="small">
              Owner
            </Text>
          </Details>
        </ProfileDetail>
        <Link onClick={() => setIsShowMore((prevState) => !prevState)} variant="button">
          {isShowMore ? 'Show less' : 'Show more'}
        </Link>
      </MobileProfileView>
      <Collapsible open={isShowMore}>
        <>
          <MerchantDetails merchantId={merchantId} />
          <Divider noMargin />
          <Verification />
          <Divider noMargin />
          <UserInfo onClick={handleEditClick} isMobile={isMobile} infoData={infoData} />
        </>
      </Collapsible>
      <Divider noMargin />
    </MobileProfileContainer>
  ) : (
    <ProfileContainer>
      <Heading size="small">Your profile</Heading>
      <ProfileContent>
        <UserProfile>
          <ProfilePhoto imageUrl={imageUrl} />
          <Details>
            <Heading size="small">{titleCase(merchantName)}</Heading>
            <Subheading>Owner</Subheading>
            <MerchantDetails merchantId={merchantId} />
            <Divider />
            <Verification />
          </Details>
        </UserProfile>
        <UserInfo onClick={handleEditClick} isMobile={isMobile} infoData={infoData} />
      </ProfileContent>
    </ProfileContainer>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    profile: state.profile,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { ...ProfileActions, ...ModalActions, updateSession, showNotification, updateUser },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(Profile);

Profile.defaultProps = {
  isMobile: isMobileDevice(),
};
