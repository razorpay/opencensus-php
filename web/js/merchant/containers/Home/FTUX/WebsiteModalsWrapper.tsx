import React, { useState } from 'react';
import { noop } from '@libs/shared-utils';

import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import NewNeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NewNeedsClarificationModal';

import { BusinessWebsiteAutomationContext } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/context';
import {
  WebsiteSubmitModalSteps,
  WebsiteUpdateApiData,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/types';
import WebsiteSubmitModal from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/WebsiteSubmitModal';

interface WebsiteModalsWrapperProps {
  onDismiss: VoidFunction;
  refreshWebsiteData: VoidFunction;
  activeStep: WebsiteSubmitModalSteps;
  websiteUpdateData?: WebsiteUpdateApiData;
  workflowType?: string;
  workflowName?: string;
}

const WebsiteModalsWrapper = ({
  onDismiss,
  activeStep,
  websiteUpdateData,
  workflowType = WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
  workflowName = 'Add Business Website',
  refreshWebsiteData = noop,
}: WebsiteModalsWrapperProps) => {
  const [currentStep, setCurrentStep] = useState<WebsiteSubmitModalSteps>(
    activeStep ?? WebsiteSubmitModalSteps.ADD_MAIN_PAGE,
  );

  return activeStep === WebsiteSubmitModalSteps.WEBSITE_NC_RAISED ? (
    <NewNeedsClarificationModal
      workflowType={workflowType}
      workflowName={workflowName}
      onDismiss={onDismiss}
      onResponseSubmit={refreshWebsiteData}
      refetch
    />
  ) : (
    <BusinessWebsiteAutomationContext.Provider
      value={{
        currentStep,
        setCurrentStep,
        websiteUpdateData,
        refetchWebsiteUpdateData: noop,
        isWebsiteDetailsFetching: false,
        isWebsiteDetailsFetchError: false,
      }}
    >
      <WebsiteSubmitModal
        isOpen={true}
        onDismiss={onDismiss}
        refreshWebsiteData={refreshWebsiteData}
      />
    </BusinessWebsiteAutomationContext.Provider>
  );
};

export default WebsiteModalsWrapper;
