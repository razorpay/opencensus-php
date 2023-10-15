import { Box } from '@razorpay/blade/components';
import { Modules } from 'common/constant/enums';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import * as ProfileActions from 'merchant/reducers/profile';
import { updateSession } from 'merchant/reducers/session';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
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
import ModalForm from 'merchant/views/AccountAndSettings/common/components/ModalForm';
import {
  PersonalProfileFields,
  ActiveModalI,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

const screen = Modules.BusinessSettings;

const AccountDetails = (props): JSX.Element => {
  const [activeModal, setActiveModal] = React.useState<ActiveModalI | null>(null);

  const { openModal, user, isMobile } = props;

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
        // eslint-disable-next-line no-lonely-if
        if (id === PersonalProfileFields.DISPLAY_NAME || id === PersonalProfileFields.NAME) {
          setActiveModal({ ...data, ...attributes });
        } else {
          openModal(modalConfig);
        }
      }
    }
  };

  const onUpdateClick = (userInput) => {
    const id = activeModal!.id;
    switch (id) {
      case PersonalProfileFields.DISPLAY_NAME:
        return activeModal!.updateMerchantConfig?.({ display_name: userInput }, () => {
          setActiveModal(null);
        });
      case PersonalProfileFields.NAME:
        return activeModal!.updateMerchantConfig?.({ name: userInput }, () => {
          setActiveModal(null);
        });
      default:
        return null;
    }
  };

  const onModalClose = () => setActiveModal(null);

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
          {ownerDetails && <DetailsViewCard title="Owner’s Account Details" info={ownerDetails} />}
        </Box>
      </TriggerOnQueryParamMatch>
      <ModalForm
        showModal={!!activeModal}
        onModalDismiss={onModalClose}
        onUpdateClick={onUpdateClick}
        entity={activeModal}
      />
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
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AccountDetails);
