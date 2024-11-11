import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import * as ProfileActions from 'merchant/reducers/profile';
import { fetchSettlementConfig } from 'merchant/reducers/settlements/details';
import {
  ACTION_QUERY_PARAM_KEY,
  UPDATE_BANK_ACCOUNT,
} from 'merchant/views/Account/Profile/deeplink-constants';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import AccountSection from './components/AccountSection';
import Banner from './components/Banner';
import HomeShimmer from './components/HomeShimmer';
import WorkflowStatus from './components/WorkflowStatus';
import { useBankAccountDetails } from './hooks/useBankAccountDetails';
import BankAccountUpdateFlow from './steps/BankAccountUpdateFlow';
import { StyledBankAccountDetails } from './styled';
import {
  ActionPayload,
  BankAccountDetailsPropsInterface,
  BANK_ACCOUNT_UPDATE_STEPS,
  FLOW_TYPE,
} from './typings';
import { useValidatePermissions } from 'merchant/helpers/permissions/utils';
import { PERMISSIONS } from 'merchant/helpers/permissions/constant';

const BankAccountDetails = ({
  openModal,
  showNotification,
  fetchBankAccount,
  fetchSettlementConfig,
  settlementConfig,
  user,
  fetchBankAccountChangeStatus,
  org,
  profile: { bankAccount },
  isMobile,
}: BankAccountDetailsPropsInterface): JSX.Element => {
  const [isBankUpdateEnabled, setIsBankUpdateEnabled] = useState<boolean | null>(null);
  const [isDataSettled, setIsDataSettled] = useState<boolean>(false);
  const { isActionAllowed, isRBACEnabled } = useValidatePermissions();
  // Get this permission assigned to admin and owner
  const canUpdateBankAccount = isActionAllowed({
    permissions: [PERMISSIONS.UPDATE_BANK_ACCOUNT_DETAIL],
  });

  const context = useTwoFactorVerificationContext();

  const {
    activeBank,
    isUpdateEnable,
    settlementStatus: { isHold, hold_type },
  } = useBankAccountDetails({
    org,
    user,
    isDataSettled,
    bankAccount,
    settlementConfig,
    isBankUpdateEnabled,
  });

  const handleBankAccountAction = ({ flowType }: ActionPayload): void => {
    const modalConfig = {
      size: 'large',
      component: (
        <BankAccountUpdateFlow defaultView={BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM} />
      ),
      overlayStyles: { padding: '12px' },
      className: 'bank-account-modal-layout',
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: UPDATE_BANK_ACCOUNT,
      },
    };
    context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        switch (flowType) {
          /* istanbul ignore next */
          case FLOW_TYPE.SWITCH:
            // as there is nothing to assert atm
            break;
          case FLOW_TYPE.UPDATE:
          default:
            openModal(modalConfig);
            break;
        }
      },
      onFlowTermination: () => {
        showNotification({
          type: 'error',
          message: 'Something went wrong, Please try again later',
        });
      },
      onBankAccountUpdateReq: true,
      isNewAccountAndSettingsPage: true,
    });
  };

  const triggerBankAccountAction = (): void =>
    handleBankAccountAction({ flowType: FLOW_TYPE.UPDATE });

  useEffect(() => {
    const { isAdminOrOwner, id } = user;
    const promises: Promise<void>[] = [fetchBankAccount(), fetchSettlementConfig()];
    if (isRBACEnabled ? canUpdateBankAccount : isAdminOrOwner) {
      promises.push(
        fetchBankAccountChangeStatus(id)
          .then(({ data }) => setIsBankUpdateEnabled(!data))
          .catch(() => {
            showNotification({
              type: 'error',
              message: 'Unable to fetch Bank Information',
            });
          }),
      );
    }
    Promise.allSettled(promises).then(() => setIsDataSettled(true));
  }, [user.id, user.isAdminOrOwner, isRBACEnabled, canUpdateBankAccount]);

  return (
    <>
      <TriggerOnQueryParamMatch
        queryParamsMapping={[
          {
            key: ACTION_QUERY_PARAM_KEY,
            value: UPDATE_BANK_ACCOUNT,
            trigger: triggerBankAccountAction,
          },
        ]}
      />
      <StyledBankAccountDetails>
        <WorkflowStatus isSettlementOnHold={isHold} />
        {hold_type && <Banner type={hold_type} />}
        {activeBank && Object.keys(activeBank).length ? (
          <AccountSection
            id="Active Bank Account"
            accountData={activeBank}
            isCtaAction={isUpdateEnable}
            handleAction={handleBankAccountAction}
          />
        ) : !isDataSettled ? (
          <HomeShimmer isMobile={isMobile} />
        ) : null}
      </StyledBankAccountDetails>
    </>
  );
};

const mapStateToProps = (state) => ({
  org: state.session.org,
  user: state.session.user,
  profile: state.profile,
  settlementConfig: state.settlement.config,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...ProfileActions,
      ...NotificationActions,
      fetchSettlementConfig,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(BankAccountDetails);
