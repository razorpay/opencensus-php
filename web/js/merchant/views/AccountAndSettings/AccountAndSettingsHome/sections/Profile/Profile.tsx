import { CenterLoader } from 'common/components/Loader';
import { Modules } from 'common/constant/enums';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import * as ProfileActions from 'merchant/reducers/profile';
import { updateSession } from 'merchant/reducers/session';
import lazy from 'merchant/routes/LazyLoader';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
import { getRole } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import {
  HANDLERS,
  InfoDataInterface,
  PersonalProfileFields,
  ProfilePropsInterface,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateUser } from 'merchant_common/reducers/user';
import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { FORM_MAP } from './handlers';
import { getEmailStatus } from 'merchant/reducers/team';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const ProfileViewV1 = lazy(() => import(/* webpackChunkName: "ProfileViewV1" */ './views/v1'));
const ProfileViewV2 = lazy(() => import(/* webpackChunkName: "ProfileViewV2" */ './views/v2'));

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
  const context = useTwoFactorVerificationContext();

  const handleEditClick = (data: InfoDataInterface): void => {
    const {
      id,
      queryParam,
      handlerType,
      isCriticalFlowEnabled,
      selfServeActionName,
      analyticsEventInfo,
      value,
    } = data;
    const { attributes = {}, Component: FormComponent } = FORM_MAP[id]?.({
      props,
      id,
      handlerType,
    });
    const screen = Modules.AccountAndSettings;
    const entity = { ...data, ...attributes };
    const isUpdateEmailFlow =
      entity?.id === PersonalProfileFields.EMAIL && entity.handlerType === HANDLERS.UPDATE;
    const isUpdateDisplayNameFlow = entity?.id === PersonalProfileFields.DISPLAY_NAME;
    const modalConfig = {
      size: 'small',
      isNew: isUpdateEmailFlow || isUpdateDisplayNameFlow,
      component: (
        <SuspenseWithLoader>
          <FormComponent
            {...attributes}
            entity={entity}
            screen={screen}
            isNewAccountAndSettingsPage
          />
        </SuspenseWithLoader>
      ),
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

  const userRole = getRole({ user });

  const commonProps = {
    user,
    isMobile,
    userRole,
    profile,
    handleEditClick,
  };

  return (
    <Suspense fallback={<CenterLoader />}>
      {user?.isContactDetailsRevamp ? (
        <ProfileViewV2 {...commonProps} />
      ) : (
        <ProfileViewV1 {...commonProps} />
      )}
    </Suspense>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    profile: state.profile,
    isMobile: state.app.isMobileResolution,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ProfileActions,
      ...ModalActions,
      updateSession,
      showNotification,
      updateUser,
      getEmailStatus,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(Profile);
