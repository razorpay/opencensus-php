import { fetchWorkflowStatus as fetchWorkflowStatusAction } from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { isVisible } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import InternationalHPBanner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/InternationalHPBanner/HPBanner';
import { BannerType } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

const workflowType = WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI;
const responseRequiredWorkflowStatus = ['open', 'approved'];
const rejectedWorkflowStatus = ['rejected'];
const successWorkflowStatus = ['executed'];

const WorkflowStatus = ({ user, fetchWorkflowStatus, workflows }): JSX.Element | null => {
  const { workflow_status, request_under_validation, tags, needs_clarification, loading } =
    workflows[workflowType] || {};

  useEffect(() => {
    if (loading) {
      fetchWorkflowStatus(workflowType);
    }
  }, [fetchWorkflowStatus, loading]);

  if (loading) return null;

  const hasRejectedStatus =
    rejectedWorkflowStatus.includes(workflow_status) &&
    !request_under_validation &&
    isVisible(true, user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI);
  const hasAwaitingCustomerResponseStatus =
    responseRequiredWorkflowStatus.includes(workflow_status) &&
    needs_clarification &&
    !request_under_validation &&
    tags?.includes('awaiting-customer-response');
  const hasSuccessStatus =
    successWorkflowStatus.includes(workflow_status) &&
    !request_under_validation &&
    isVisible(true, user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI);

  const getBannerType = (): BannerType | null => {
    if (hasSuccessStatus) {
      return BannerType.APPROVED;
    } else if (hasAwaitingCustomerResponseStatus) {
      return BannerType.NEEDS_CLARIFICATION;
    } else if (hasRejectedStatus) {
      return BannerType.REJECTED;
    }
    return null;
  };
  const type = getBannerType();

  return type && <InternationalHPBanner type={type} />;
};

export default compose(
  connect(
    (state) => ({
      workflows: state.workflows,
      user: state.session.user,
    }),
    {
      fetchWorkflowStatus: fetchWorkflowStatusAction,
    },
  ),
)(WorkflowStatus);
