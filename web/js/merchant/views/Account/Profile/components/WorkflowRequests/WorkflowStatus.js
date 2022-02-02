import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';

export const isWorkflowInClarification = (workflow, statuses) => {
  if (!workflow) return false;
  const { workflow_status, needs_clarification, request_under_validation } = workflow;
  return statuses.includes(workflow_status) && needs_clarification && !request_under_validation;
};

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
  showReviewStatus = true,
  showResponseRequiredStatus = true,
  showRespondedStatus = true,
  showRejectedStatus = true,
  showAddReplyButton = true,
  workflows,
  reviewStatus,
  fetchWorkflowStatus,
  user,
}) => {
  useEffect(() => {
    // Only fetch request if user is owner, other users shouldn't see the workflow
    if ((!roles.length || roles.includes(user.role)) && workflows[workflowType].loading) {
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

  return (
    <>
      {/* Request in review */}
      {showReviewStatus &&
        ((reviewWorkflowStatus.includes(workflow_status) && !needs_clarification) ||
          request_under_validation) && <div class="workflow-status inprogress">{reviewStatus}</div>}

      {/* Request Rejected */}
      {showRejectedStatus &&
        rejectedWorkflowStatus.includes(workflow_status) &&
        !request_under_validation && (
          <div class="workflow-status rejected">{rejection_reason_message}</div>
        )}

      {/* Needs Clarification Customer Responded */}
      {showRespondedStatus &&
        isWorkflowInClarification(workflow, respondedWorkflowStatus) &&
        tags?.includes('customer-responded') && (
          <div class="workflow-status inprogress">
            Thank you for providing us with further information. Our team is going through the
            information provided by you and will help resolve this issue.
          </div>
        )}

      {/* Needs Clarification Awaiting Customer Response */}
      {showResponseRequiredStatus &&
        isWorkflowInClarification(workflow, responseRequiredWorkflowStatus) &&
        tags?.includes('awaiting-customer-response') && (
          <div class="workflow-status rejected">
            {needs_clarification}
            {showAddReplyButton && (
              <button class="btn btn-link" onClick={onReplyClick ? onReplyClick : null}>
                Add Reply
              </button>
            )}
          </div>
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
