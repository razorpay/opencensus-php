import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import DetailRow from 'merchant/components/DetailRow';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import TextHighlighter from 'common/ui/TextHighlighter';
import { UPDATE_BANK_ACC } from 'merchant/views/Account/Profile/deeplink-constants';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import NeedsClarificationModal from './WorkflowRequests/NeedsClarificationModal';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import rolesList from 'merchant/helpers/permissions/roles-list';

const BankAccountDetails = ({
  bankAccount,
  bank_detail_update_workflow,
  onChangeBankAccountDetails,
  isBankAccountChangeAllowed,
  settlement_amount,
  location,
  user,
  bankAccountChangeStatus,
  settlementConfig,
  openModal,
  org,
}) => {
  const bankAccountSectionRef = useRef(null);

  const openNeedsClarificationModal = (data) => {
    analyticsTrack({
      objectName: 'needs clarification respond',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        flowName: data.workflowType,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    openModal({
      size: 'small',
      component: <NeedsClarificationModal {...data} />,
    });
  };

  const isWorkflowChangeAllowed = (workflow) => {
    return (
      !workflow?.loading &&
      (workflow?.workflow_exists === false ||
        !['open', 'approved'].includes(workflow?.workflow_status))
    );
  };

  useEffect(() => {
    if (
      bankAccountSectionRef &&
      bankAccountSectionRef.current &&
      location &&
      location.hash === '#request-bank-account-change'
    ) {
      bankAccountSectionRef.current.scrollIntoView();
    }
  }, [bankAccountSectionRef, location]);

  const isSettlementOnHold = settlement_amount?.no_settlement?.on_hold;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

  // Hide Bank Account "Request Change" button if org feature is enabled
  const hideRequestChange = org.features.indexOf('block_account_update') > -1;

  const showRequestChange =
    !isSettlementOnHold &&
    isBankAccountChangeAllowed !== null &&
    !user.blockBankAccountUpdate() &&
    user.activation_status === 'activated' &&
    !hideRequestChange &&
    !(isOnTemporaryHold && bankAccountChangeStatus) &&
    (bank_detail_update_workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(bank_detail_update_workflow?.workflow_status));

  return (
    <div className="panel panel-default" ref={bankAccountSectionRef}>
      <div className="panel-heading">
        <TextHighlighter hashedWith={UPDATE_BANK_ACC}>Bank Account</TextHighlighter>
        {isSettlementOnHold && !hideRequestChange && (
          <span className="pull-right gray">
            <span>Request Change</span>
            <small className="help-content">
              <i className="i i-info-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div>The bank account cannot be updated, since your funds are on hold.</div>
                </PopoverBody>
              </Popover>
            </small>
          </span>
        )}
        {isOnTemporaryHold && bankAccountChangeStatus && (
          <span className="pull-right bank-details-review">
            <i className="i i-info-outline pr-5" /> Request to update bank account details is under
            review
          </span>
        )}
        {showRequestChange &&
          (isWorkflowChangeAllowed(bank_detail_update_workflow) && isBankAccountChangeAllowed ? (
            <span
              className="nav-link pull-right"
              onClick={(...e) => {
                analyticsTrack({
                  objectName: 'bank account edit',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    action: 'cancel',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                onChangeBankAccountDetails(...e);
              }}
            >
              Request Change
            </span>
          ) : null)}
        <WorkflowStatus
          roles={[rolesList.OWNER]}
          workflowType={WORKFLOW_TYPES.BANK_DETAIL_UPDATE}
          reviewStatus=" Your request to update your bank account has been received. Our team is going
                    through the information provided by you."
          onReplyClick={() =>
            openNeedsClarificationModal({
              workflowType: WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
              workflowName: 'Update Bank Account Details',
            })
          }
        />
      </div>
      <div className="list-group details-row-container">
        <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow
          label={() => (
            <div class="bank-account">
              <span>Beneficiary</span>
            </div>
          )}
          value={bankAccount.name}
        />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  bankAccountChangeStatus: state.profile.bankAccountChangeStatus,
  settlementConfig: state.settlement.config,
  workflows: state.workflows,
  bank_detail_update_workflow: state.workflows[WORKFLOW_TYPES.BANK_DETAIL_UPDATE],
  org: state.session.org,
});

export default withRouter(
  connect(mapStateToProps, {
    openModal: fnOpenModal,
  })(BankAccountDetails),
);
