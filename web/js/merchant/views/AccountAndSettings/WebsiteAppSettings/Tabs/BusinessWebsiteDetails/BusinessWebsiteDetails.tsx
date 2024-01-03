import Button from 'common/new-ui/Button';
import { Store } from 'common/typings';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, isPresent } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import {
  fetchWorkflowStatus as fetchWorkflowStatusReducer,
  fetchBusinessWebsiteFeatureStatus,
} from 'merchant/reducers/workflows';
import EditWebsiteDetailsModal from 'merchant/views/Account/Profile/components/EditWebsiteDetailsModal';
import { FLOWS } from 'merchant/views/Account/Profile/components/WebsiteSelfServe/Constants';
import InitiateWebsiteChange from 'merchant/views/Account/Profile/components/WebsiteSelfServe/InitiateWebsiteChange';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import {
  ACTION_QUERY_PARAM_KEY,
  UPDATE_WEBSITE_DETAILS,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { BusinessWebsiteDetailsProps } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/typings';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  Box,
  Text,
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
  Button as BladeButton,
  PlusIcon,
  EditIcon,
  Alert,
} from '@razorpay/blade/components';
import {
  StyledBusinessWebsiteContainer,
  StyledLinksContainer,
  StyledAdditionalWebsiteContainer,
} from './styled';
import { useMobile } from 'common/hooks/useMobile';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import DetailRow from 'merchant/components/DetailRow';
import rolesList from 'merchant/helpers/permissions/roles-list';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import { isWorkflowChangeAllowed, useBusinessWebsiteRevamp } from './utils';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';

const renderWebsites = (user, handleEditWebsite, websiteWorkflow) => {
  return (
    <div>
      {user.business_website ? (
        <span className="text-primary m-r">
          <a href={user.business_website} target="_blank" rel="noopener noreferrer">
            {user.business_website}
          </a>
        </span>
      ) : (
        '--'
      )}
      {isWorkflowChangeAllowed(websiteWorkflow) &&
        user.role === 'owner' &&
        user.isWebsiteSelfServeOn && (
          <TriggerOnQueryParamMatch
            queryParamsMapping={[
              {
                key: ACTION_QUERY_PARAM_KEY,
                value: UPDATE_WEBSITE_DETAILS,
                trigger: () => handleEditWebsite(FLOWS.BUSINESS_WEBSITE),
              },
            ]}
          >
            <Button.Transparent
              type="button"
              data-testid="business-website-edit"
              onClick={() => {
                handleEditWebsite(FLOWS.BUSINESS_WEBSITE);
              }}
            >
              <i className="i i-edit p-l" />
            </Button.Transparent>
          </TriggerOnQueryParamMatch>
        )}
    </div>
  );
};

const renderAdditionalWebsites = (user, handleEditWebsite, additionalWebsiteWorkflow) => {
  const hasAdditionalWebsites = isPresent(user.additional_websites);
  const isLimitReached = hasAdditionalWebsites ? user.additional_websites.length === 5 : false;

  return (
    <div className="website-self-serve__listItem additional-websites__listcontainer">
      <div>
        {hasAdditionalWebsites ? (
          user.additional_websites.map((website, idx) => (
            <div key={`${website}_${idx}`}>
              <a href={website} target="_blank" rel="noopener noreferrer">
                {website}
                {idx === user.additional_websites.length - 1 ? '' : ','}
              </a>
            </div>
          ))
        ) : (
          <span className="additional-websites__listcontainer">--</span>
        )}
      </div>
      {user.business_website &&
        user.isAdditionalDomainWhitelistSelfServeOn &&
        isWorkflowChangeAllowed(additionalWebsiteWorkflow) &&
        (user.role === 'owner' || user.role === 'admin') &&
        !isLimitReached && (
          <div>
            <Button.Transparent
              type="button"
              data-testid="additional-business-website-edit"
              onClick={() => {
                selfServeTrackInitiate({
                  selfServeAction: 'Additional Website - App Url Updated',
                  page: user?.isAccountAndSettingsRevampEnabled
                    ? 'Business Website Details'
                    : 'Profile',
                  screen: user?.isAccountAndSettingsRevampEnabled
                    ? 'Account & Settings'
                    : 'My Account',
                });
                handleEditWebsite(FLOWS.ADDITIONAL_WEBSITE);
              }}
            >
              <i className="i i-plus p-l" />
            </Button.Transparent>
          </div>
        )}
    </div>
  );
};

const BusinessWebsiteDetails = (props: BusinessWebsiteDetailsProps): JSX.Element => {
  const {
    user,
    workflows,
    fetchWorkflowStatus,
    openModal,
    closeModal,
    isFlowRevamped = true,
    fetchBusinessWebsiteFeatureStatus,
  } = props;

  const businessWebsiteWorkflow = workflows[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];
  const additionalWebsiteWorkflow = workflows[WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE];
  const isMobile = useMobile();
  const isRevamp = useBusinessWebsiteRevamp();
  const businessWebsiteAutomationStatus = workflows.business_website_automation_status;

  useEffect(() => {
    // FETCH FOR BUSINESS WEBSITE
    if (isRevamp && [rolesList.OWNER].includes(user.role as string)) {
      fetchBusinessWebsiteFeatureStatus(user.id as string);
      fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE);
    }
  }, []);

  const handleEditWebsite = (flowType) => {
    const { has_key_access: hasWebsite, business_website, isActivated } = user;

    // If true => edit website flow; otherwise add flow
    if (hasWebsite || (business_website && isActivated && flowType === FLOWS.BUSINESS_WEBSITE)) {
      openModal({
        size: 'small',
        component: (
          <InitiateWebsiteChange
            user={user}
            openModal={openModal}
            closeModal={closeModal}
            flowType={flowType}
          />
        ),
        queryParams: {
          [ACTION_QUERY_PARAM_KEY]: UPDATE_WEBSITE_DETAILS,
        },
      });
    } else {
      openModal({
        size: 'small',
        component: (
          <EditWebsiteDetailsModal
            onClose={closeModal}
            onWebsiteAdd={() => fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE)}
          />
        ),
        queryParams: {
          [ACTION_QUERY_PARAM_KEY]: UPDATE_WEBSITE_DETAILS,
        },
      });
    }

    // Avoid tracking for additional website flow
    if (flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    let analyticsObject;

    // Edit flow
    if (hasWebsite || (business_website && isActivated && flowType === FLOWS.BUSINESS_WEBSITE)) {
      analyticsObject = {
        objectName: `Website edit`,
        actionName: 'Edit clicked',
        screen: 'My account',
        properties: {
          currentWebsite: `${user.business_website}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    } else {
      // Add flow
      analyticsObject = {
        objectName: `Website add`,
        actionName: 'Add clicked',
        screen: 'My account',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    }

    analyticsTrack(analyticsObject);
  };

  const openNeedsClarificationModal = (data) => {
    analyticsTrack({
      objectName: 'needs clarification respond',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        flowName: data.workflowType,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    openModal({
      size: 'small',
      component: <NeedsClarificationModal {...data} />,
    });
  };

  const showAlert = () => {
    const {
      workflow_status,
      needs_clarification,
      request_under_validation,
      tags,
      rejection_reason_message,
    } = businessWebsiteWorkflow;
    const { data } = businessWebsiteAutomationStatus;

    if (data?.status === true) {
      return (
        <Alert
          contrast="low"
          description={
            user.has_key_access === true
              ? 'Your request to update the website is under review.'
              : "Our team will verify your website/app so that you can start collecting payments on it. We'll contact you via email if we need further information."
          }
          intent="notice"
          isDismissible={false}
          isFullWidth
        />
      );
    }

    const respondedWorkflowStatus = ['open', 'approved'];
    const responseRequiredWorkflowStatus = ['open', 'approved'];
    const rejectedWorkflowStatus = ['rejected'];
    const reviewWorkflowStatus = ['open', 'approved'];

    const hasReviewStatus =
      (reviewWorkflowStatus.includes(workflow_status) && !needs_clarification) ||
      request_under_validation;

    const hasRejectedStatus =
      rejectedWorkflowStatus.includes(workflow_status) && !request_under_validation;

    const hasCustomerRespondedStatus =
      isWorkflowInClarification(businessWebsiteWorkflow, respondedWorkflowStatus) &&
      tags?.includes('customer-responded');

    const hasAwaitingCustomerResponseStatus =
      isWorkflowInClarification(businessWebsiteWorkflow, responseRequiredWorkflowStatus) &&
      tags?.includes('awaiting-customer-response');

    if (hasCustomerRespondedStatus) {
      return (
        <Alert
          contrast="low"
          description="Thank you for providing us with further information. Our team is going through the information provided by you and will help resolve this issue"
          intent="notice"
          isDismissible={false}
          isFullWidth
        />
      );
    } else if (hasAwaitingCustomerResponseStatus) {
      return (
        <Alert
          contrast="low"
          description={needs_clarification}
          intent="negative"
          isDismissible={false}
          actions={{
            primary: {
              text: 'Add reply',
              onClick: () =>
                openNeedsClarificationModal({
                  workflowType: WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
                  workflowName:
                    businessWebsiteWorkflow.permission === 'edit_merchant_website_detail'
                      ? 'Add Business Website'
                      : 'Update Business Website',
                }),
            },
          }}
          isFullWidth
        />
      );
    } else if (hasRejectedStatus) {
      return (
        <Alert
          contrast="low"
          description={rejection_reason_message}
          intent="negative"
          isDismissible={true}
          isFullWidth
        />
      );
    } else if (hasReviewStatus) {
      return (
        <Alert
          contrast="low"
          description={
            user.has_key_access === true
              ? 'Your request to update the website is under review'
              : "Our team will verify your website/app so that you can start collecting payments on it. We'll contact you via email if we need further information"
          }
          intent="notice"
          isDismissible={false}
          isFullWidth
        />
      );
    }

    return null;
  };

  if (isRevamp) {
    return (
      <>
        <StyledBusinessWebsiteContainer style={{ position: 'relative' }}>
          <Card padding="spacing.3" elevation="none" testID="website-card-container">
            <CardHeader>
              <CardHeaderLeading title="Business website/app detail" />
            </CardHeader>
            <CardBody>
              <Box marginBottom={'spacing.7'}>
                <Text>
                  This is the website/app where payments can be collected after integration of the
                  payment gateway
                </Text>
              </Box>
              <Box>{showAlert()}</Box>
              <StyledLinksContainer>
                <Box
                  borderColor="surface.border.normal.lowContrast"
                  marginY={'spacing.5'}
                  borderRadius="small"
                  padding={'spacing.7'}
                  display="flex"
                  overflow="scroll"
                  width="100%"
                >
                  <Box
                    borderColor="surface.border.normal.lowContrast"
                    padding={'spacing.3'}
                    borderRadius="medium"
                    backgroundColor="surface.background.level1.lowContrast"
                  >
                    <img
                      src="https://cdn.razorpay.com/static/assets/globe.svg"
                      alt="globe"
                      width="30px"
                    />
                  </Box>
                  <Box marginLeft={'spacing.7'}>
                    <Text color="surface.text.subtle.lowContrast" weight="bold">
                      Website Url
                    </Text>
                    <Text marginTop={'spacing.3'}>
                      {user.business_website ? user.business_website : '--'}
                    </Text>
                  </Box>
                </Box>
                <Box
                  borderColor="surface.border.normal.lowContrast"
                  marginY={'spacing.5'}
                  borderRadius="small"
                  padding={'spacing.7'}
                  display="flex"
                  overflow="scroll"
                  width="100%"
                >
                  <Box
                    borderColor="surface.border.normal.lowContrast"
                    padding={'spacing.3'}
                    borderRadius="medium"
                    backgroundColor="surface.background.level1.lowContrast"
                  >
                    <img
                      src="https://cdn.razorpay.com/static/assets/globe.svg"
                      alt="globe"
                      width="30px"
                    />
                  </Box>
                  <Box marginLeft={'spacing.7'}>
                    <Text color="surface.text.subtle.lowContrast" weight="bold">
                      AppStore Url
                    </Text>
                    <Text marginTop={'spacing.3'}>
                      {user.appstore_url ? user.appstore_url : '--'}
                    </Text>
                  </Box>
                </Box>
                <Box
                  borderColor="surface.border.normal.lowContrast"
                  marginY={'spacing.5'}
                  borderRadius="small"
                  padding={'spacing.7'}
                  display="flex"
                  overflow="scroll"
                  width="100%"
                >
                  <Box
                    borderColor="surface.border.normal.lowContrast"
                    padding={'spacing.3'}
                    borderRadius="medium"
                    backgroundColor="surface.background.level1.lowContrast"
                  >
                    <img
                      src="https://cdn.razorpay.com/static/assets/globe.svg"
                      alt="globe"
                      width="30px"
                    />
                  </Box>
                  <Box marginLeft={'spacing.7'}>
                    <Text color="surface.text.subtle.lowContrast" weight="bold">
                      PlayStore Url
                    </Text>
                    <Text marginTop={'spacing.3'}>
                      {user.playstore_url ? user.playstore_url : '--'}
                    </Text>
                  </Box>
                </Box>
              </StyledLinksContainer>
            </CardBody>
          </Card>
          {isWorkflowChangeAllowed(businessWebsiteWorkflow) &&
          businessWebsiteAutomationStatus?.data?.status === false &&
          user.role === 'admin' ? (
            <Box position="absolute" top={isMobile ? '12px' : '0px'} right="15px">
              {isMobile ? (
                <BladeButton
                  icon={user.business_website ? EditIcon : PlusIcon}
                  onClick={() => handleEditWebsite(FLOWS.BUSINESS_WEBSITE)}
                />
              ) : (
                <BladeButton
                  iconPosition="left"
                  icon={user.business_website ? EditIcon : PlusIcon}
                  onClick={() => handleEditWebsite(FLOWS.BUSINESS_WEBSITE)}
                >
                  Add website/app details
                </BladeButton>
              )}
            </Box>
          ) : null}
        </StyledBusinessWebsiteContainer>
        <StyledAdditionalWebsiteContainer
          className={`${isFlowRevamped ? 'details-row-container' : ''}`}
        >
          <DetailRow
            label={() => (
              <div className="website-self-serve__listItem">
                <span>Additional Business Website/App</span>
                <small className="help-content">
                  <i className="i i-info-outline" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>
                        <div>
                          {user?.isOrgCurlec
                            ? ATTR_DETAILS.curlec_additional_website_info.desc
                            : ATTR_DETAILS.additional_website_info.desc}
                        </div>
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
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
              </div>
            )}
            value={() =>
              renderAdditionalWebsites(user, handleEditWebsite, additionalWebsiteWorkflow)
            }
          />
        </StyledAdditionalWebsiteContainer>
      </>
    );
  }

  return (
    <div className={`${isFlowRevamped ? 'list-group details-row-container' : ''}`}>
      <DetailRow
        label={() => (
          <div className="website-self-serve__listItem">
            <span>Business Website/App details</span>
            <small className="help-content">
              <i className="i i-info-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div>
                    <div>These are the verified websites on which payments can be integrated</div>
                  </div>
                </PopoverBody>
              </Popover>
            </small>
            <WorkflowStatus
              roles={[rolesList.OWNER]}
              workflowType={WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE}
              reviewStatus={
                user.has_key_access === true
                  ? 'Your request to update the website is under review.'
                  : "Our team will verify your website/app so that you can start collecting payments on it. We'll contact you via email if we need further information."
              }
              onReplyClick={() =>
                openNeedsClarificationModal({
                  workflowType: WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
                  workflowName:
                    businessWebsiteWorkflow.permission === 'edit_merchant_website_detail'
                      ? 'Add Business Website'
                      : 'Update Business Website',
                })
              }
            />
          </div>
        )}
        value={() => renderWebsites(user, handleEditWebsite, businessWebsiteWorkflow)}
      />
      <DetailRow
        label={() => (
          <div className="website-self-serve__listItem">
            <span>Additional Business Website/App</span>
            <small className="help-content">
              <i className="i i-info-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div>
                    <div>
                      {user?.isOrgCurlec
                        ? ATTR_DETAILS.curlec_additional_website_info.desc
                        : ATTR_DETAILS.additional_website_info.desc}
                    </div>
                  </div>
                </PopoverBody>
              </Popover>
            </small>
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
          </div>
        )}
        value={() => renderAdditionalWebsites(user, handleEditWebsite, additionalWebsiteWorkflow)}
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
      fetchBusinessWebsiteFeatureStatus,
    },
    dispatch,
  );

export default connect(
  (state: Store) => ({ user: state.session.user, workflows: state.workflows }),
  mapDispatchToProps,
)(BusinessWebsiteDetails);
