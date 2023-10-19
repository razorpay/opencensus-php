import React from 'react';
import { connect } from 'react-redux';
import { OTPMETHOD } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/services';
import SuccessModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/SuccessModal';
import OTPModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/OTPModal';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import * as ModalActions from 'merchant_common/reducers/modals';
import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';
import AccountDetailsUpdate from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2/AccountDetailsUpdate';
import { AddEmailProps } from './types';

const AddEmail = ({
  screen,
  user,
  openModal,
  entity,
  entity: { queryParam },
  closeModal,
}: AddEmailProps) => {
  const onClose = () => {
    analyticsTrack({
      objectName: 'add email pop up cancel',
      actionName: 'clicked',
      screen: 'add email',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const renderModal = ({ isNew = false, component }) => {
    closeModal();
    openModal({
      size: 'small',
      isNew,
      component,
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: queryParam,
      },
    });
  };

  const onEmailOTPSubmit = (newEmail) => () => {
    renderModal({
      component: (
        <SuccessModal
          heading="Email added successfully &#127881;"
          screen={screen}
          note={`Your email ${newEmail} has been added successfully`}
          onClose={onClose}
        />
      ),
    });
    selfServeTrackSuccess({
      selfServeAction: 'Login Details Updated',
      page: 'Personal Profile',
      screen: 'Account & Settings',
    });
  };

  const onEnterEmailSubmit =
    (otpAuthToken) =>
    ({ userInput: email, setIsLoading }) => {
      entity.onEmailAdd?.({ email, otpAuthToken, setIsLoading }, () => {
        renderModal({
          component: (
            <OTPModal
              otpMethod={OTPMETHOD.EMAIL}
              email={email}
              screen={screen}
              otpAuthToken={otpAuthToken}
              onSubmit={onEmailOTPSubmit(email)}
              onClose={onClose}
            />
          ),
        });
      });
    };

  const onMobileOTPSubmit = ({ token }) => {
    renderModal({
      isNew: true,
      component: (
        <AccountDetailsUpdate
          entity={entity}
          onEnterEmailSubmit={onEnterEmailSubmit(token)}
          onModalDismiss={onClose}
        />
      ),
    });
  };

  return (
    <OTPModal
      otpMethod={OTPMETHOD.PHONE}
      onSubmit={onMobileOTPSubmit}
      phone={user.user?.contact_mobile}
      screen={screen}
      onClose={onClose}
    />
  );
};

export default connect((state) => ({ user: state.session.user }), {
  showNotification: fnShowNotification,
  ...ModalActions,
})(AddEmail);
