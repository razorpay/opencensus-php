import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';

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
  roles,
  reviewWorkflowStatus = ['open', 'approved'],
  responseRequiredWorfklowStatus = ['open', 'approved'],
  respondedWorkflowStatus = ['open', 'approved'],
  rejectedWorkflowStatus = ['rejected'],
  showReviewStatus = true,
  showResponseRequiredStatus = true,
  showRespondedStatus = true,
  showRejectedStatus = true,
  workflows,
  reviewStatus,
  fetchWorkflowStatus,
  user,
}) => {
  useEffect(() => {
    // Only fetch request if user is owner, other users shouldn't see the error
    if (!roles || (roles && roles.includes(user.role))) fetchWorkflowStatus(workflowType);
  }, []);

  return (
    <>
      {showReviewStatus &&
        workflows[workflowType]?.workflow_status &&
        reviewWorkflowStatus.includes(workflows[workflowType]?.workflow_status) &&
        !workflows[workflowType]?.needs_clarification && (
          <div class="workflow-status inprogress">{reviewStatus}</div>
        )}
      {showRejectedStatus &&
        workflows[workflowType]?.workflow_status &&
        rejectedWorkflowStatus.includes(workflows[workflowType]?.workflow_status) && (
          <div class="workflow-status rejected">
            {workflows[workflowType]?.rejection_reason_message}
          </div>
        )}
      {/* Needs Clarification */}
      {showResponseRequiredStatus &&
        workflows[workflowType]?.workflow_status &&
        respondedWorkflowStatus.includes(workflows[workflowType]?.workflow_status) &&
        workflows[workflowType]?.needs_clarification &&
        workflows[workflowType]?.tags?.includes('customer-responded') && (
          <div class="workflow-status inprogress">
            Thank you for providing us with further information. Our team is going through the
            information provided by you and will help resolve this issue.
          </div>
        )}
      {showRespondedStatus &&
        workflows[workflowType]?.workflow_status &&
        responseRequiredWorfklowStatus.includes(workflows[workflowType]?.workflow_status) &&
        workflows[workflowType]?.needs_clarification &&
        workflows[workflowType]?.tags?.includes('awaiting-customer-response') && (
          <div class="workflow-status rejected">
            {workflows[workflowType]?.needs_clarification}
            <button class="btn btn-link" onClick={onReplyClick ? onReplyClick : null}>
              Add Reply
            </button>
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
