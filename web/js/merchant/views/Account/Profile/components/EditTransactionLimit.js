import React, { useCallback, useMemo } from 'react';
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
import {
  NC_INCREASE_TXN_LIMIT,
  RR_INCREASE_TXN_LIMIT,
  ACTION_QUERY_PARAM_KEY,
  CHANGE_DOMESTIC_TRANSACTION_LIMIT,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { bindActionCreators } from 'redux';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';

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
  const { transactionType, workflows, user, openModal } = props;
  const { merchant: { max_payment_amount, max_international_payment_amount } = {} } = user;
  const isTypeDomestic = transactionType === 'domestic';
  const workflowKey = isTypeDomestic
    ? WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT
    : WORKFLOW_TYPES.INCREASE_INTERNATIONAL_TRANSACTION_LIMIT;

  const amountValue = useMemo(
    () => (isTypeDomestic ? max_payment_amount : max_international_payment_amount),
    [isTypeDomestic, max_international_payment_amount, max_payment_amount],
  );

  const showTransactionLimitEdit = useMemo(() => {
    const { loading, workflow_exists, workflow_status } = workflows[workflowKey] ?? {};
    const isWorkflowChangeAllowed =
      !loading && (!workflow_exists || !['open', 'approved'].includes(workflow_status));

    // Unregistered government and gaming merchants aren't allowed to edit transaction limit
    const isMerchantAllowedToEditLimit = !(
      user.isUnregisteredBusiness && ['government', 'gaming'].includes(user.business_category)
    );

    const showTransactionLimit =
      user.role === 'owner' &&
      user.isOrgRZP &&
      user.isINCountry &&
      user.isTransactionLimitUpdateSelfServeOn &&
      isWorkflowChangeAllowed &&
      isMerchantAllowedToEditLimit;

    return showTransactionLimit;
  }, [
    user.business_category,
    user.isOrgRZP,
    user.isINCountry,
    user.isTransactionLimitUpdateSelfServeOn,
    user.isUnregisteredBusiness,
    user.role,
    workflowKey,
    workflows,
  ]);

  const updateHandler = useCallback(() => {
    openModal({
      component: (
        <UpdateTransactionLimit
          onComplete={() => fetchWorkflowStatus(workflowKey)}
          closeModal={closeModal}
          transactionType={transactionType}
        />
      ),
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: CHANGE_DOMESTIC_TRANSACTION_LIMIT,
      },
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
  }, [amountValue, isTypeDomestic, openModal, transactionType, workflowKey]);

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
                  The maximum {user.merchant.currency} limit for only a single {transactionType}{' '}
                  transaction
                </PopoverBody>
              </Popover>
            </small>
            <WorkflowStatus
              roles={[rolesList.OWNER]}
              workflowType={workflowKey}
              reviewStatus="Your request to increase transaction limit has been received. Our team is going through the information provided by you."
              onReplyClick={() =>
                props.replyHandler({
                  workflowType: workflowKey,
                  workflowName: 'Increase Transaction Limit',
                })
              }
            />
          </div>
        )}
        value={() =>
          !user?.international && !isTypeDomestic ? (
            <span>
              <Link to="/payment-methods?instrument=international" onClick={linkHandler}>
                Apply for international
              </Link>
            </span>
          ) : (
            <div>
              {amountValue ? (
                <Amount value={amountValue} currency={user.merchant.currency} />
              ) : (
                'Not Updated'
              )}
              {showTransactionLimitEdit && (
                <TriggerOnQueryParamMatch
                  queryParamsMapping={[
                    {
                      key: ACTION_QUERY_PARAM_KEY,
                      value: CHANGE_DOMESTIC_TRANSACTION_LIMIT,
                      trigger: updateHandler,
                    },
                  ]}
                >
                  <Button.Transparent type="button" onClick={updateHandler}>
                    <i className="i i-edit p-l" />
                  </Button.Transparent>
                </TriggerOnQueryParamMatch>
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
