import React, { useEffect, useMemo, useState } from 'react';
import {
  Box,
  Button,
  Heading,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useMobile } from 'common/hooks/useMobile';
import {
  OpenModalType,
  CloseModalType,
  Store,
  User as UserType,
  ShowNotificationType,
} from 'common/typings';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { FLOWS } from 'merchant/views/Account/Profile/components/WebsiteSelfServe/Constants';
import InitiateWebsiteChange from 'merchant/views/Account/Profile/components/WebsiteSelfServe/InitiateWebsiteChange';
import UpdateWebsiteDetails from 'merchant/views/Account/Profile/components/WebsiteSelfServe/UpdateWebsiteDetails';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';
import User from 'merchant/models/User';
import { updateSession as updateSessionReducer } from 'merchant/reducers/session';

import RenderWebsites from './RenderWebsites';
import WebsiteSubmitModal from './WebsiteSubmitModal';
import WorkflowAndAlerts from './WorkflowAndAlerts';
import useBusinessWebsiteData from './hooks/useBusinessWebsiteData';
import { trackEditWebsiteIconClick, trackWebsiteAppDetailsButtonClick } from './tracking';
import { AddWebsiteClickArgs, WebsiteSubmitModalSteps, WebsiteUpdateActionOn } from './types';
import { getCTACondition, getWebsiteCount } from './utils';

interface BusinessWebsiteDetailsProps {
  user: UserType;
  org: { business_name: string };
  workflows: Record<string, any>;
  fetchWorkflowStatus: (workflowType: string) => {
    type: string;
    payload: Promise<any>;
  };
  openModal: OpenModalType;
  closeModal: CloseModalType;
  showNotification: ShowNotificationType;
  updateSession: (args: { user: UserType; mode?: string }) => void;
}

const BusinessWebsiteDetails: React.FC<BusinessWebsiteDetailsProps> = ({
  user,
  workflows,
  fetchWorkflowStatus,
  openModal,
  showNotification,
  org,
  updateSession,
}) => {
  const isMobile = useMobile(mobileBreakoints);
  const [openModalName, setOpenModalName] = useState('');

  const {
    setCurrentStep,
    websiteUpdateData,
    isWebsiteDetailsFetching,
    isWebsiteDetailsFetchError,
  } = useBusinessWebsiteData();

  const businessWebsiteWorkflow = workflows[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];
  const additionalWebsiteWorkflow = workflows[WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE];

  const {
    isMainWebsiteEditActionAllowed,
    isAddActionAllowed,
    isAddFirstWebsiteAllowed,
    ctaText,
    ctaDisabledReason,
  } = useMemo(
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
          isBladeRevamp={true}
          isMobile={isMobile}
          {...(actionOn === WebsiteUpdateActionOn.ADDITIONAL_WEBSITE
            ? {
                flowType: FLOWS.ADDITIONAL_WEBSITE,
                openNewModal: () => {
                  closeModal();
                  setOpenModalName(FLOWS.ADDITIONAL_WEBSITE);
                },
              }
            : {
                flowType: FLOWS.BUSINESS_WEBSITE,
                openNewModal: () => {
                  closeModal();
                  setOpenModalName(FLOWS.BUSINESS_WEBSITE);
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

  const onDismissModal = () => {
    fetchWorkflows([WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE]);
    setOpenModalName('');
  };

  const onDismiss = () => {
    fetchWorkflows([WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE]);
    setOpenModalName('');
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);
  };

  const onFixMissingPages = () => {
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
    setOpenModalName(FLOWS.BUSINESS_WEBSITE);
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
          <Box>
            {isAddActionAllowed ? (
              <Button
                isDisabled={false}
                onClick={() =>
                  handleClickAddWebsite({
                    actionOn: isAddFirstWebsiteAllowed
                      ? WebsiteUpdateActionOn.MAIN_WEBSITE
                      : WebsiteUpdateActionOn.ADDITIONAL_WEBSITE,
                  })
                }
              >
                {ctaText}
              </Button>
            ) : (
              <Tooltip content={ctaDisabledReason} placement="bottom">
                <TooltipInteractiveWrapper>
                  <Button isDisabled={true}>{ctaText}</Button>
                </TooltipInteractiveWrapper>
              </Tooltip>
            )}
          </Box>
        </Box>
        <WorkflowAndAlerts
          openModal={openModal}
          businessWebsiteWorkflow={businessWebsiteWorkflow}
          websiteUpdateData={websiteUpdateData}
          onFixMissingPages={onFixMissingPages}
        />
        <RenderWebsites
          isMainWebsiteEditActionAllowed={isMainWebsiteEditActionAllowed}
          isWebsiteDetailsFetching={isWebsiteDetailsFetching}
          isMobile={isMobile}
          onClickAddWebsite={handleClickAddWebsite}
          user={user}
          businessWebsiteWorkflow={businessWebsiteWorkflow}
          websiteUpdateData={websiteUpdateData}
          ctaDisabledReason={ctaDisabledReason}
        />
      </Box>
      <WebsiteSubmitModal
        isOpen={openModalName === FLOWS.BUSINESS_WEBSITE}
        onDismiss={onDismiss}
        updateUserSession={(userData) => {
          const newUser = new User(userData);
          updateSession({
            user: newUser as unknown as UserType,
          });
        }}
      />
      {openModalName === FLOWS.ADDITIONAL_WEBSITE ? (
        <UpdateWebsiteDetails
          flowType={FLOWS.ADDITIONAL_WEBSITE}
          isOpen={true}
          shouldShowV2={true}
          onDismiss={onDismissModal}
        />
      ) : null}
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
      updateSession: updateSessionReducer,
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
