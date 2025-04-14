import React from 'react';
import { Box } from '@razorpay/blade/components';

import { OpenModalType } from 'common/typings';
import rolesList from 'merchant/helpers/permissions/roles-list';
import NewNeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NewNeedsClarificationModal';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';

import PrimaryWebsiteWorkflowStatus from './components/PrimaryWebsiteWorkflowStatus';
import { WebsiteUpdateApiData } from './types';

interface WorkflowAndAlertsProps {
  openModal: OpenModalType;
  businessWebsiteWorkflow: Record<string, any>;
  websiteUpdateData: WebsiteUpdateApiData | undefined;
  onFixMissingPages: () => void;
}

const WorkflowAndAlerts: React.FC<WorkflowAndAlertsProps> = ({
  openModal,
  businessWebsiteWorkflow,
  websiteUpdateData,
  onFixMissingPages,
}) => {
  const openNeedsClarificationModal = (data) => {
    openModal({
      size: 'small',
      component: <NewNeedsClarificationModal {...data} />,
    });
  };

  const { current_status, current_status_updated_at, main_page_url } = websiteUpdateData || {};

  return (
    <Box marginY="spacing.6">
      {/* Primary website workflow */}
      <PrimaryWebsiteWorkflowStatus
        websiteUpdateData={websiteUpdateData}
        businessWebsiteWorkflow={businessWebsiteWorkflow}
        onFixMissingPages={onFixMissingPages}
        onReplyClick={() =>
          openNeedsClarificationModal({
            workflowType: WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
            workflowName:
              businessWebsiteWorkflow.permission === 'edit_merchant_website_detail'
                ? 'Add Business Website'
                : 'Update Business Website',
          })
        }
        key={`${current_status}-${main_page_url}-${current_status_updated_at}`}
      />

      {/* Additonal website workflow */}
      <WorkflowStatus
        roles={[rolesList.OWNER, rolesList.ADMIN]}
        workflowType={WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE}
        reviewStatus="Your request to add the website is under review. We'll contact you via email if we need further information."
        onReplyClick={() =>
          openNeedsClarificationModal({
            workflowType: WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE,
            workflowName: 'Add Additional Website',
          })
        }
      />
    </Box>
  );
};

export default WorkflowAndAlerts;
