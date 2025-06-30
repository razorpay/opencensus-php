import React, { useEffect, useRef, useMemo } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
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
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const REVIEW_STATUS =
  'Your bank account change request is under review - This should take 2-3 working days';

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
      size: 'large',
      component: <NeedsClarificationModal {...data} />,
      className: 'bank-account-details-change-modal',
    });
  };

  const isWorkflowChangeAllowed = (workflow) => {
    return (
      !workflow?.loading &&
      (workflow?.workflow_exists === false ||
        !['open', 'approved'].includes(workflow?.workflow_status))
    );
  };

  /**
   * if merchant has `opgsp_import_flow` or `enable_jpmc_import_flow` feature enabled then disable Change bank account.
   * Because Bank account for such merchants will be added during onboarding and
   * merchant is not allowed to update that. It can only be done via admin dashboard.
   */
  const isOpgspImportMerchant = useMemo(() => {
    return Array.isArray(user.tags)
      ? user.tags.some((tag) =>
          ['opgsp_import_flow', 'enable_jpmc_import_flow'].includes(tag.toLowerCase()),
        )
      : false;
  }, [user.tags]);

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

  const { abExperiments } = useSplitzService();

  const showRequestChange =
    !isOpgspImportMerchant &&
    !isSettlementOnHold &&
    isBankAccountChangeAllowed !== null &&
    !isExperimentEnabled(abExperiments.block_bank_account_update) &&
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
          (isWorkflowChangeAllowed(bank_detail_update_workflow) &&
          isBankAccountChangeAllowed &&
          (user.isCountryIndia) ? (
            <span
              className="nav-link pull-right"
              onClick={(...e) => {
                selfServeTrackInitiate({
                  selfServeAction: 'Bank Account Updated',
                  page: 'Profile',
                  screen: 'My Account',
                });
                analyticsTrack({
                  objectName: 'bank account edit',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    action: 'cancel',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                analyticsTrack({
                  objectName: 'Bank Account Update Edit',
                  actionName: 'Clicked',
                  screen: 'My Account',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                onChangeBankAccountDetails(...e);
              }}
            >
              Change bank account
            </span>
          ) : null)}
      </div>
      <WorkflowStatus
        roles={[rolesList.OWNER]}
        workflowType={WORKFLOW_TYPES.BANK_DETAIL_UPDATE}
        reviewStatus={REVIEW_STATUS}
        onReplyClick={() =>
          openNeedsClarificationModal({
            workflowType: WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
            workflowName: 'Change your bank account',
          })
        }
        isBankAccountUpdateWorkflow
        successStatus="Your bank account is successfully changed"
        showSuccessStatus
        customerRespondedStatus={REVIEW_STATUS}
      />
      <div className="list-group details-row-container">
        {(user.isCountryIndia) && (
          <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        )}
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow
          label={() => (
            <div className="bank-account">
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
