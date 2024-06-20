import React, { useEffect, useMemo, useState } from 'react';
import { Box, Button, Heading, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useMobile } from 'common/hooks/useMobile';
import { OpenModalType, CloseModalType, Store, User, ShowNotificationType } from 'common/typings';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { FLOWS } from 'merchant/views/Account/Profile/components/WebsiteSelfServe/Constants';
import InitiateWebsiteChange from 'merchant/views/Account/Profile/components/WebsiteSelfServe/InitiateWebsiteChange';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

import RenderWebsites from './RenderWebsites';
import WebsiteSubmitModal from './WebsiteSubmitModal';
import WorkflowAndAlerts from './WorkflowAndAlerts';
import useBusinessWebsiteData from './hooks/useBusinessWebsiteData';
import { trackEditWebsiteIconClick, trackWebsiteAppDetailsButtonClick } from './tracking';
import { AddWebsiteClickArgs, WebsiteSubmitModalSteps, WebsiteUpdateActionOn } from './types';
import { getCTACondition, getWebsiteCount } from './utils';

interface BusinessWebsiteDetailsProps {
  user: User;
  org: { business_name: string };
  workflows: Record<string, any>;
  fetchWorkflowStatus: (workflowType: string) => {
    type: string;
    payload: Promise<any>;
  };
  openModal: OpenModalType;
  closeModal: CloseModalType;
  showNotification: ShowNotificationType;
}

const BusinessWebsiteDetails: React.FC<BusinessWebsiteDetailsProps> = ({
  user,
  workflows,
  fetchWorkflowStatus,
  openModal,
  showNotification,
  org,
}) => {
  const isMobile = useMobile(mobileBreakoints);
  const [isOpen, setIsOpen] = useState(false);

  const {
    setCurrentStep,
    websiteUpdateData,
    isWebsiteDetailsFetching,
    isWebsiteDetailsFetchError,
  } = useBusinessWebsiteData();

  const businessWebsiteWorkflow = workflows[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];
  const additionalWebsiteWorkflow = workflows[WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE];

  const { isMainWebsiteEditActionAllowed, isAddActionAllowed, isAddFirstWebsiteAllowed } = useMemo(
    () =>
      getCTACondition({
        isWebsiteDetailsFetching,
        isWebsiteDetailsFetchError,
        websiteUpdateData,
        additionalWebsiteWorkflow,
        businessWebsiteWorkflow,
        user,
      }),
    [
      isWebsiteDetailsFetching,
      isWebsiteDetailsFetchError,
      websiteUpdateData,
      additionalWebsiteWorkflow,
      businessWebsiteWorkflow,
      user,
    ],
  );

  const handleClickAddWebsite = ({ actionOn, isEdit }: AddWebsiteClickArgs) => {
    const properties = {
      websiteType: actionOn,
      websiteCount: getWebsiteCount(user),
    };
    if (isEdit) {
      trackEditWebsiteIconClick(properties);
    } else {
      trackWebsiteAppDetailsButtonClick(properties);
    }
    openModal({
      size: 'small',
      component: (
        <InitiateWebsiteChange
          user={user}
          openModal={openModal}
          closeModal={closeModal}
          {...(actionOn === WebsiteUpdateActionOn.ADDITIONAL_WEBSITE
            ? {
                flowType: FLOWS.ADDITIONAL_WEBSITE,
              }
            : {
                flowType: FLOWS.MAIN_WEBSITE,
                openNewModal: () => {
                  closeModal();
                  setIsOpen(true);
                },
              })}
        />
      ),
    });
  };

  function fetchWorkflows(
    workflowTypes: string[] = [
      WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
      WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE,
    ],
  ) {
    /* istanbul ignore next */
    if (user.isAdminOrOwner) {
      workflowTypes.forEach((workflowType) => fetchWorkflowStatus(workflowType));
    }
  }

  const onDismiss = () => {
    fetchWorkflows([WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE]);
    setIsOpen(false);
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);
  };

  const onFixMissingPages = () => {
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
    setIsOpen(true);
  };

  useEffect(() => {
    if (businessWebsiteWorkflow?.error || additionalWebsiteWorkflow?.error) {
      showNotification({
        type: 'error',
        message: 'Failed to fetch workflow details, please try again.',
      });
    }
  }, [businessWebsiteWorkflow?.error, additionalWebsiteWorkflow?.error]);

  useEffect(() => {
    fetchWorkflows();
  }, []);

  return (
    <Box>
      <Box marginBottom="spacing.11" padding={isMobile ? 'spacing.4' : 'none'}>
        <Box
          display="flex"
          flexDirection={isMobile ? 'column' : 'row'}
          alignItems={isMobile ? 'flex-start' : 'unset'}
          justifyContent="space-between"
        >
          <Box marginBottom={isMobile ? 'spacing.4' : 'spacing.0'}>
            <Heading>Business website/app details</Heading>
            <Text size="medium" color="surface.text.gray.subtle">
              Verified websites/apps integrated with {org.business_name} Payment Gateway
            </Text>
          </Box>
          <Button
            isDisabled={!isAddActionAllowed}
            onClick={() =>
              handleClickAddWebsite({
                actionOn: isAddFirstWebsiteAllowed
                  ? WebsiteUpdateActionOn.MAIN_WEBSITE
                  : WebsiteUpdateActionOn.ADDITIONAL_WEBSITE,
              })
            }
          >
            {isAddFirstWebsiteAllowed
              ? 'Add website/app details'
              : 'Add additional website/app details'}
          </Button>
        </Box>
        <WorkflowAndAlerts
          openModal={openModal}
          businessWebsiteWorkflow={businessWebsiteWorkflow}
          websiteUpdateData={websiteUpdateData}
          onFixMissingPages={onFixMissingPages}
        />
        <RenderWebsites
          isMainWebsiteEditActionAllowed={isMainWebsiteEditActionAllowed}
          isMobile={isMobile}
          onClickAddWebsite={handleClickAddWebsite}
          user={user}
          businessWebsiteWorkflow={businessWebsiteWorkflow}
          websiteUpdateData={websiteUpdateData}
        />
      </Box>
      <WebsiteSubmitModal isOpen={isOpen} onDismiss={onDismiss} />
    </Box>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
      showNotification: showNotificationReducer,
    },
    dispatch,
  );

export default connect(
  (state: Store) => ({
    org: state.session.org,
    user: state.session.user,
    workflows: state.workflows,
  }),
  mapDispatchToProps,
)(BusinessWebsiteDetails);
