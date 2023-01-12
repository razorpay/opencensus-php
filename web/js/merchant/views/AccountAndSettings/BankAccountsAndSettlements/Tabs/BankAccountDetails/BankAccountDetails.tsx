import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ProfileActions from 'merchant/reducers/profile';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import BankAccountDetails from 'merchant/views/Account/Profile/components/BankAccountDetails';
import BankAccountDetailsChange from 'merchant/views/Account/Profile/components/BankAccountDetailsChange';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import {
  ACTION_QUERY_PARAM_KEY,
  UPDATE_BANK_ACCOUNT,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { bindActionCreators } from 'redux';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  BankVerificationErrorInDetailsMap,
  getResponseTime,
  trackBankAccountDetailsChange,
} from 'merchant/views/Account/Profile/components/BankAccountDetailsChangeSteps';
import moment from 'moment';
import {
  AnyObject,
  BankAccountDetailsContainerProps,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/typings';
import LoaderDots from 'common/ui/LoaderDots';
import { NoBankAccount } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetails/Styled';
import { Modules } from 'common/constant/enums';

const BankAccountDetailsContainer = ({
  fetchBankAccount,
  fetchSettlementAmount,
  user,
  fetchBankAccountChangeStatus,
  profile: { bankAccount },
  openModal,
  saveBankAccountChangesAutomate,
  fetchWorkflowStatus,
  closeModal,
  showNotification,
  saveBankAccountChanges: saveBankAccountChangesProp,
  settlement_amount,
}: BankAccountDetailsContainerProps) => {
  const [isBankAccountChangeAllowed, setIsBankAccountChangeAllowed] = useState<boolean | null>(
    null,
  );
  const context = useTwoFactorVerificationContext();

  useEffect(() => {
    fetchBankAccount();
    fetchSettlementAmount();
    if (user.isAdminOrOwner) {
      fetchBankAccountChangeStatus(user.id) //user.id is merchant_id not user_id
        .then(({ data }) => setIsBankAccountChangeAllowed(!data))
        .catch(() => {
          console.log('ERROR: Failed to fetch bank account change status');
        });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const saveBankAccountChanges = (
    data,
    setBankDetailsStepCallback: (data?: AnyObject) => void = () => {},
  ) => {
    const body = { ...data };
    const formdata = new FormData();

    //not needed
    delete body.account_number_confirmation;
    analyticsTrackWithUserInfo({
      objectName: 'Bank account save',
      actionName: 'clicked',
      screen: Modules.AccountAndSettings,
    });
    //required fields for api
    body.beneficiary_email = user.email;
    body.beneficiary_mobile = user.contact_mobile;

    //for the new flow
    body.sync_only = true;

    for (const prop in body) {
      // istanbul ignore else
      if (body.hasOwnProperty(prop)) {
        formdata.append(prop, body[prop]);
      }
    }

    if (user.bankAccountAutoUpdateOrWorkflow()) {
      setBankDetailsStepCallback({
        state: 'penny-testing-started',
      });
      trackBankAccountDetailsChange({
        objectName: 'Bank Account Update Submit',
        actionName: 'Request',
      });
      const requestStartedAt = new Date();
      return saveBankAccountChangesAutomate(user.id, formdata) //user.id is merchant_id not user_id
        .then(({ data }) => {
          trackBankAccountDetailsChange({
            objectName: 'Bank Account Update Submit',
            actionName: 'Result',
            properties: {
              status: 'success',
              responseTime: getResponseTime(requestStartedAt),
              requestType: data?.sync_flow ? 'sync' : 'async',
            },
          });
          selfServeTrackSuccess({
            selfServeAction: 'Bank Account Update',
            page: 'Profile',
            screen: 'My Account',
          });
          if (data.new_bank_account && data.sync_flow === true) {
            fetchBankAccount();
            setBankDetailsStepCallback({
              state: 'penny-testing-success',
            });
            fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
          } else {
            // penny-testing failure or timeout
            const workflowStatusKey = `${WORKFLOW_TYPES.BANK_DETAIL_UPDATE}--${user.id}`;
            const workflowStatus = JSON.parse(localStorage.getItem('workflow_status') || '{}');
            const newWorkflowStatus = {
              ...workflowStatus,
              [workflowStatusKey]: {
                expireAt: moment().add(15, 'days').format(),
                isVisible: true,
              },
            };
            localStorage.setItem('workflow_status', JSON.stringify(newWorkflowStatus));
            setBankDetailsStepCallback({
              state: 'sync-failed-async-started',
            });
          }
          if (data.timeout) {
            trackBankAccountDetailsChange({
              objectName: 'Bank Account Request',
              actionName: 'Timeout',
            });
          }
        })
        .catch(({ errors }) => {
          const inputError =
            errors?.[0] in BankVerificationErrorInDetailsMap
              ? BankVerificationErrorInDetailsMap[errors[0]]
              : null;

          if (inputError) {
            // bank verification error because of user input
            setBankDetailsStepCallback({
              state: 'penny-testing-details-error',
              error: inputError,
            });
          } else {
            closeModal();
            showNotification({
              type: 'error',
              message: errors,
            });
          }
          trackBankAccountDetailsChange({
            objectName: 'Bank Account Update Submit',
            actionName: 'Result',
            properties: {
              status: 'failure',
              responseTime: getResponseTime(requestStartedAt),
              errorMessage: inputError ? errors?.[0] : `${errors}`,
            },
          });
        });
    }

    return saveBankAccountChangesProp(user.id, formdata) //user.id is merchant_id not user_id
      .then(() => {
        closeModal();
        fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
        showNotification({
          type: 'success',
          message: 'Bank Account change request updated succesfully. ',
        });
        setIsBankAccountChangeAllowed(false);
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  const openChangeBankDetailsModal = () => {
    return context.criticalFlow({
      modes: ['live'],
      onUserTwoFaVerified: () => {
        openModal({
          size: 'large',
          component: (
            <BankAccountDetailsChange
              currentBankAccount={bankAccount}
              onSave={saveBankAccountChanges}
            />
          ),
          overlayStyles: { padding: '10px' },
          className: 'bank-account-details-change-modal',
          queryParams: {
            [ACTION_QUERY_PARAM_KEY]: UPDATE_BANK_ACCOUNT,
          },
        });
      },
      onBankAccountUpdateReq: true,
      isNewAccountAndSettingsPage: true,
    });
  };

  if (bankAccount === null) {
    return <NoBankAccount>No bank account details found!</NoBankAccount>;
  }

  if (!bankAccount) {
    return <LoaderDots />;
  }

  return (
    <>
      <TriggerOnQueryParamMatch
        queryParamsMapping={[
          {
            key: ACTION_QUERY_PARAM_KEY,
            value: UPDATE_BANK_ACCOUNT,
            trigger: openChangeBankDetailsModal,
          },
        ]}
      />
      <BankAccountDetails
        bankAccount={bankAccount}
        isBankAccountChangeAllowed={isBankAccountChangeAllowed}
        settlement_amount={settlement_amount.data}
        onChangeBankAccountDetails={openChangeBankDetailsModal}
      />
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    profile: state.profile,
    settlement_amount: state.home.settlement_amount,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ProfileActions,
      ...ModalActions,
      showNotification,
      fetchSettlementAmount,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(BankAccountDetailsContainer);
