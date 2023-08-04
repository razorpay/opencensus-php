import Button from 'common/new-ui/Button';
import { Store } from 'common/typings';
import Popover, { PopoverBody } from 'common/ui/Popover';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, isPresent } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import DetailRow from 'merchant/components/DetailRow';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import EditWebsiteDetailsModal from 'merchant/views/Account/Profile/components/EditWebsiteDetailsModal';
import { FLOWS } from 'merchant/views/Account/Profile/components/WebsiteSelfServe/Constants';
import InitiateWebsiteChange from 'merchant/views/Account/Profile/components/WebsiteSelfServe/InitiateWebsiteChange';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import {
  ACTION_QUERY_PARAM_KEY,
  UPDATE_WEBSITE_DETAILS,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { BusinessWebsiteDetailsProps } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/typings';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const isWorkflowChangeAllowed = (workflow) => {
  return (
    !workflow?.loading &&
    (workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(workflow?.workflow_status))
  );
};

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
        user.isAccepted &&
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
  } = props;

  const businessWebsiteWorkflow = workflows[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];
  const additionalWebsiteWorkflow = workflows[WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE];

  const handleEditWebsite = (flowType) => {
    const { has_key_access: hasWebsite, business_website, isActivated } = user;

    // If true => edit website flow; otherwise add flow
    if (hasWebsite || (business_website && isActivated)) {
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
    if (hasWebsite || (business_website && isActivated)) {
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
    },
    dispatch,
  );

export default connect(
  (state: Store) => ({ user: state.session.user, workflows: state.workflows }),
  mapDispatchToProps,
)(BusinessWebsiteDetails);
