import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { isWorkflowInClarification, isVisible } from './utils';
import ReviewStatusContent from './components/ReviewStatusContent';
import RejectedStatusContent from './components/RejectedStatusContent';
import CustomerRespondedContent from './components/CustomerRespondedContent';
import AwaitingCustomerResponseContent from './components/AwaitingCustomerResponseContent';
import SuccessStatusContent from './components/SuccessStatusContent';

/**
 * Shows following workflow status based on the workflow status response
 * - No Status (Default condition)
 * - Request Under Review - Shows `reviewStatus` string if request is initiated from merchant's end
 * - Rejected - Shows `rejection_reason` form status response
 * - Needs Clarification Awaiting Customer Response - shows `needs_clarification` from status response
 * - Needs Clarification Customer Responded - show a common thank you message
 * @param {*} {
 *   roles,
 *   workflowType,
 *   reviewStatus,
 *   onReplyClick
 * }
 * @return {*} `React.Component`
 */
const WorkflowStatus = ({
  workflowType,
  onReplyClick,
  roles = [],
  reviewWorkflowStatus = ['open', 'approved'],
  responseRequiredWorkflowStatus = ['open', 'approved'],
  respondedWorkflowStatus = ['open', 'approved'],
  rejectedWorkflowStatus = ['rejected'],
  successWorkflowStatus = ['executed'],
  showReviewStatus = true,
  showResponseRequiredStatus = true,
  showRespondedStatus = true,
  showRejectedStatus = true,
  showAddReplyButton = true,
  showSuccessStatus = false,
  successStatus = 'Your request is successfully exectuted',
  customerRespondedStatus = 'Thank you for providing us with further information. Our team is going through the information provided by you and will help resolve this issue.',
  workflows,
  reviewStatus,
  fetchWorkflowStatus,
  user,
  isBankAccountUpdateWorkflow = false,
  shouldSkipRoleCheck = false,
}) => {
  const shouldFetchWorkflow = shouldSkipRoleCheck
    ? true
    : !roles.length || roles.includes(user.role);

  useEffect(() => {
    // Only fetch request if user is owner, other users shouldn't see the workflow
    if (shouldFetchWorkflow && workflows[workflowType].loading) {
      fetchWorkflowStatus(workflowType);
    }
  }, []);

  const workflow = workflows[workflowType];
  if (!workflow) return null;

  const {
    workflow_status,
    needs_clarification,
    request_under_validation,
    tags,
    rejection_reason_message,
  } = workflow;

  const hasReviewStatus =
    showReviewStatus &&
    ((reviewWorkflowStatus.includes(workflow_status) && !needs_clarification) ||
      request_under_validation);

  const hasRejectedStatus =
    showRejectedStatus &&
    rejectedWorkflowStatus.includes(workflow_status) &&
    !request_under_validation &&
    isVisible(isBankAccountUpdateWorkflow, user.id);

  const hasCustomerRespondedStatus =
    showRespondedStatus &&
    isWorkflowInClarification(workflow, respondedWorkflowStatus) &&
    tags?.includes('customer-responded');

  const hasAwaitingCustomerResponseStatus =
    showResponseRequiredStatus &&
    isWorkflowInClarification(workflow, responseRequiredWorkflowStatus) &&
    tags?.includes('awaiting-customer-response');

  const hasSuccessStatus =
    showSuccessStatus &&
    successWorkflowStatus.includes(workflow_status) &&
    !request_under_validation &&
    isVisible(isBankAccountUpdateWorkflow, user.id);

  return (
    <>
      {/* Request in review */}
      {hasReviewStatus && (
        <ReviewStatusContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          content={reviewStatus}
        />
      )}

      {/* Request Rejected */}
      {hasRejectedStatus && (
        <RejectedStatusContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          content={rejection_reason_message}
          user={user}
        />
      )}

      {/* Needs Clarification Customer Responded */}
      {hasCustomerRespondedStatus && (
        <CustomerRespondedContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          content={customerRespondedStatus}
        />
      )}

      {/* Needs Clarification Awaiting Customer Response */}
      {hasAwaitingCustomerResponseStatus && (
        <AwaitingCustomerResponseContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          content={customerRespondedStatus}
          needsClarificationMessage={needs_clarification}
          showAddReplyButton={showAddReplyButton}
          onReplyClick={onReplyClick}
        />
      )}

      {/* Request Successfully executed */}
      {hasSuccessStatus && (
        <SuccessStatusContent
          isBankAccountUpdateWorkflow={isBankAccountUpdateWorkflow}
          content={successStatus}
          user={user}
        />
      )}
    </>
  );
};

export default compose(
  connect(
    (state) => ({
      workflows: state.workflows,
      user: state.session.user,
    }),
    {
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
  ),
)(WorkflowStatus);
