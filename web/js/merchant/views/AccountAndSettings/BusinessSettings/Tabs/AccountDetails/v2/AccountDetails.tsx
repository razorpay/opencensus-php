import { Box } from '@razorpay/blade/components';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import * as ProfileActions from 'merchant/reducers/profile';
import { updateSession } from 'merchant/reducers/session';
import { getRole } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import { FORM_MAP } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/handlers';
import DetailsViewCard from 'merchant/views/AccountAndSettings/BusinessSettings/components/DetailsViewCard';
import { StyledTabContentContainer } from 'merchant/views/AccountAndSettings/styled';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateUser, updateUserName } from 'merchant_common/reducers/user';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  getOwnerDetails,
  getQueryParamMapping,
  getUserAccountDetails,
  makeAnalytics,
} from './utils';
import {
  PersonalProfileFields,
  HANDLERS,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { getEmailStatus } from 'merchant/reducers/team';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
import { Modules } from 'common/constant/enums';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const screen = Modules.BusinessSettings;

const AccountDetails = (props): JSX.Element => {
  const { openModal, user, isMobile, closeModal } = props;
  const context = useTwoFactorVerificationContext();
  const userRole = getRole({ user });
  const ownerDetails = getOwnerDetails({ userRole, user });
  const userDetails = getUserAccountDetails({ user });

  const handleEditAction = (data): void => {
    const { id, queryParam, handlerType, isCriticalFlowEnabled, isEditEnable } = data;
    if (isEditEnable && FORM_MAP[id]) {
      const { attributes = {}, Component: FormComponent } = FORM_MAP[id]?.({
        props,
        id,
        handlerType,
      });
      const entity = { ...data, ...attributes };
      const isAddEmailFlow =
        entity?.id === PersonalProfileFields.EMAIL && entity.handlerType === HANDLERS.ADD;
      const isContactMobileEdit = entity?.id === PersonalProfileFields.CONTACT_MOBILE;
      const modalConfig = {
        size: 'small',
        isNew: !isAddEmailFlow && !isContactMobileEdit,
        component: (
          <SuspenseWithLoader>
            <FormComponent
              {...attributes}
              entity={entity}
              closeModal={closeModal}
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

      makeAnalytics(data);

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
    }
  };

  const queryparamMap = getQueryParamMapping({ userDetails, handleEditAction });
  const WrapperComponent = !isMobile ? StyledTabContentContainer : React.Fragment;

  return (
    <WrapperComponent className="content">
      <TriggerOnQueryParamMatch queryParamsMapping={queryparamMap}>
        <Box display="flex" flexDirection="column" gap={{ base: 'spacing.5', m: 'spacing.7' }}>
          <DetailsViewCard
            title="Your Account Details"
            info={userDetails}
            handleAction={handleEditAction}
          />
          {ownerDetails && <DetailsViewCard title="Owner's Account Details" info={ownerDetails} />}
        </Box>
      </TriggerOnQueryParamMatch>
    </WrapperComponent>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ProfileActions,
      ...ModalActions,
      showNotification,
      updateSession,
      updateUser,
      updateUserName,
      getEmailStatus,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AccountDetails);
