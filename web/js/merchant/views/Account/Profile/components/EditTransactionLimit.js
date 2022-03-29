import React, { useCallback } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import IntoView from 'common/ui/IntoView';
import DetailRow from 'merchant/components/DetailRow';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import UpdateTransactionLimit from './UpdateTransactionLimit';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { fetchWorkflowStatus } from 'merchant/reducers/workflows';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { NC_INCREASE_TXN_LIMIT, RR_INCREASE_TXN_LIMIT } from '../deeplink-constants';
import { bindActionCreators } from 'redux';

function isWorkflowChangeAllowed(workflow) {
  return (
    !workflow?.loading &&
    (workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(workflow?.workflow_status))
  );
}

function linkHandler() {
  analyticsTrack({
    objectName: 'Apply for international',
    actionName: 'clicked',
    screen: 'my account',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
}

const EditTransactionLimit = (props) => {
  const { transactionType, workflows, user } = props;
  const isTypeDomestic = transactionType === 'domestic';

  const amountValue = isTypeDomestic
    ? user.merchant.max_payment_amount
    : user.merchant.max_international_payment_amount;

  const showTransactionLimitEdit = useCallback(() => {
    const increaseTxnLimitWorkflow = workflows[WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT];
    const increaseIntlTxnLimitWorkflow = user?.international
      ? workflows[WORKFLOW_TYPES.INCREASE_INTERNATIONAL_TRANSACTION_LIMIT]
      : null;

    const canEdit = isTypeDomestic
      ? isWorkflowChangeAllowed(increaseTxnLimitWorkflow)
      : isWorkflowChangeAllowed(increaseIntlTxnLimitWorkflow);

    const key = isTypeDomestic
      ? 'increase_transaction_limit'
      : 'increase_international_transaction_limit';

    const workflowCheck =
      workflows[key]?.workflow_exists === false ||
      !['open', 'approved'].includes(workflows[key]?.workflow_status);

    // Unregistered government and gaming merchants aren't allowed to edit transaction limit
    const isMerchantAllowedToEditLimit =
      user.isUnregisteredBusiness && ['government', 'gaming'].includes(user.business_category);

    const showTransactionLimit =
      user.role === 'owner' &&
      user.isOrgRZP &&
      user.isTransactionLimitUpdateSelfServeOn &&
      workflowCheck &&
      isMerchantAllowedToEditLimit &&
      canEdit;

    if (showTransactionLimit) return true;

    return false;
  }, [
    isTypeDomestic,
    user.business_category,
    user.international,
    user.isOrgRZP,
    user.isTransactionLimitUpdateSelfServeOn,
    user.isUnregisteredBusiness,
    user.role,
    workflows,
  ]);

  const updateHandler = useCallback(() => {
    const key = isTypeDomestic
      ? 'INCREASE_TRANSACTION_LIMIT'
      : 'INCREASE_INTERNATIONAL_TRANSACTION_LIMIT';

    props.openModal({
      component: (
        <UpdateTransactionLimit
          onComplete={() => fetchWorkflowStatus(WORKFLOW_TYPES[key])}
          closeModal={closeModal}
          transactionType={transactionType}
        />
      ),
    });

    // Track when user click on edit limit
    analyticsTrack({
      objectName: isTypeDomestic
        ? 'Transaction limit edit'
        : 'International Transaction limit edit',
      actionName: 'Merchant clicks on edit',
      screen: 'My account screen',
      properties: {
        currentLimit: `${amountValue}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, [amountValue, isTypeDomestic, transactionType]);

  return (
    <IntoView hashedWith={[NC_INCREASE_TXN_LIMIT, RR_INCREASE_TXN_LIMIT]}>
      <DetailRow
        label={() => (
          <div className="transaction-limit">
            <span>{`Limit per ${transactionType} transaction`}</span>
            <small className="help-content">
              <i className="i i-info-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  The maximum INR limit for only a single {transactionType} transaction
                </PopoverBody>
              </Popover>
            </small>
            <WorkflowStatus
              roles={[rolesList.OWNER]}
              workflowType={
                isTypeDomestic
                  ? WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT
                  : WORKFLOW_TYPES.INCREASE_INTERNATIONAL_TRANSACTION_LIMIT
              }
              reviewStatus="You request to increase to transaction limit has been received. Our team is going through the information provided by you."
              onReplyClick={() =>
                props.replyHandler({
                  workflowType: isTypeDomestic
                    ? WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT
                    : WORKFLOW_TYPES.INCREASE_INTERNATIONAL_TRANSACTION_LIMIT,
                  workflowName: 'Increase Transaction Limit',
                })
              }
            />
          </div>
        )}
        value={() =>
          !user?.international && !isTypeDomestic ? (
            <span>
              <Link to="/payment-methods" onClick={linkHandler}>
                Apply for international
              </Link>
            </span>
          ) : (
            <div>
              <Amount value={amountValue} currency="INR" />
              {showTransactionLimitEdit() && (
                <Button.Transparent type="button" onClick={updateHandler}>
                  <i className="i i-edit p-l" />
                </Button.Transparent>
              )}
            </div>
          )
        }
      />
    </IntoView>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    workflows: state.workflows,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal,
      closeModal,
      fetchWorkflowStatus,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(EditTransactionLimit);
