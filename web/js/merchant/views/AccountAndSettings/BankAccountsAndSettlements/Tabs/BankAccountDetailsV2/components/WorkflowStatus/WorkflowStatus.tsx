import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchBankAccount as fetchBankAccountReducer } from 'merchant/reducers/profile';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import {
  isVisible,
  isWorkflowInClarification,
} from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import BankAccountUpdateBanner from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BankAccountUpdateBanner';
import Banner from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner';
import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import moment from 'moment';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

const {
  SUCCESS,
  ACTIVE_SETTLEMENT_UNDER_REVIEW,
  ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
  ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
  INACTIVE_SETTLEMENT_UNDER_REVIEW,
  INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
  INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
  ACTIVE_SETTLEMENT_NC,
  INACTIVE_SETTLEMENT_NC,
  ACTIVE_SETTLEMENT_REJECTED,
  INACTIVE_SETTLEMENT_REJECTED,
} = BannerType;
const workflowType = WORKFLOW_TYPES.BANK_DETAIL_UPDATE;
const isBankAccountUpdateWorkflow = true;
const reviewWorkflowStatus = ['open', 'approved'];
const responseRequiredWorkflowStatus = ['open', 'approved'];
const respondedWorkflowStatus = ['open', 'approved'];
const rejectedWorkflowStatus = ['rejected'];

const WorkflowStatus = ({
  isHomepageWorkflow,
  fetchWorkflowStatus,
  user,
  workflows,
  isSettlementOnHold,
  profile: { bankAccount },
  fetchBankAccount,
}): JSX.Element | null => {
  const worflow = workflows[workflowType];

  useEffect(() => {
    if (isHomepageWorkflow) {
      fetchBankAccount();
    }
  }, [isHomepageWorkflow]);

  useEffect(() => {
    // Only fetch request if user is owner, other users shouldn't see the workflow
    if (user.role === rolesList.OWNER && (worflow.loading || isHomepageWorkflow)) {
      fetchWorkflowStatus(workflowType);
    }
  }, [fetchWorkflowStatus, user.role, worflow.loading, isHomepageWorkflow]);

  const workflow = workflows[workflowType];
  if (workflow.loading) return null;

  const {
    workflow_status,
    needs_clarification,
    request_under_validation,
    tags,
    workflow_created_at,
    bank_account_id,
  } = workflow;

  const hasReviewStatus =
    (reviewWorkflowStatus.includes(workflow_status) && !needs_clarification) ||
    request_under_validation;
  const hasRejectedStatus =
    rejectedWorkflowStatus.includes(workflow_status) &&
    !request_under_validation &&
    isVisible(isBankAccountUpdateWorkflow, user.id);
  const hasCustomerRespondedStatus =
    isWorkflowInClarification(workflow, respondedWorkflowStatus) &&
    tags?.includes('customer-responded');
  const hasAwaitingCustomerResponseStatus =
    isWorkflowInClarification(workflow, responseRequiredWorkflowStatus) &&
    tags?.includes('awaiting-customer-response');
  const hasSuccessStatus =
    bank_account_id &&
    bankAccount?.id === bank_account_id &&
    isVisible(isBankAccountUpdateWorkflow, user.id);

  let eta;
  const getUnderReviewBannerType = ({
    underReview,
    underReviewTimeBreached,
    underReviewTimeBreachedAgain,
  }: {
    underReview: BannerType;
    underReviewTimeBreached: BannerType;
    underReviewTimeBreachedAgain: BannerType;
  }): BannerType => {
    const today = moment();
    const createdAt = moment.unix(workflow_created_at);
    const breachTime = moment(createdAt).add(2, 'days');
    const nextBreachTime = moment(createdAt).add(6, 'days');
    if (today.isBefore(breachTime)) {
      eta = moment(breachTime).format('MMM DD, YYYY');
      return underReview;
    } else if (today.isBefore(nextBreachTime)) {
      eta = moment(nextBreachTime).format('MMM DD, YYYY');
      return underReviewTimeBreached;
    }
    return underReviewTimeBreachedAgain;
  };

  const getBannerType = (): BannerType | null => {
    if (hasSuccessStatus) {
      return SUCCESS;
    } else if (isSettlementOnHold) {
      if (hasReviewStatus || hasCustomerRespondedStatus) {
        return getUnderReviewBannerType({
          underReview: INACTIVE_SETTLEMENT_UNDER_REVIEW,
          underReviewTimeBreached: INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
          underReviewTimeBreachedAgain: INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
        });
      } else if (hasRejectedStatus) {
        return INACTIVE_SETTLEMENT_REJECTED;
      } else if (hasAwaitingCustomerResponseStatus) {
        return INACTIVE_SETTLEMENT_NC;
      }
    } else if (hasReviewStatus || hasCustomerRespondedStatus) {
      return getUnderReviewBannerType({
        underReview: ACTIVE_SETTLEMENT_UNDER_REVIEW,
        underReviewTimeBreached: ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
        underReviewTimeBreachedAgain: ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
      });
    } else if (hasRejectedStatus) {
      return ACTIVE_SETTLEMENT_REJECTED;
    } else if (hasAwaitingCustomerResponseStatus) {
      return ACTIVE_SETTLEMENT_NC;
    }
    return null;
  };
  const type = getBannerType();

  if (isHomepageWorkflow) {
    return type && <BankAccountUpdateBanner type={type} />;
  }
  return type && <Banner type={type} workflowEta={eta} />;
};

export default compose(
  connect(
    (state) => ({
      workflows: state.workflows,
      user: state.session.user,
      profile: state.profile,
    }),
    {
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
      fetchBankAccount: fetchBankAccountReducer,
    },
  ),
)(WorkflowStatus);
