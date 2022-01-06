import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import rTracking from 'react-tracking';
import Time from 'common/ui/Time';
import { ProgressBar } from 'common/ui/ProgressBar';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { titleCase, isPresent, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import { BUSINESS_TYPE_MAP, ATTR_DETAILS } from 'merchant/views/Account/constants';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import UserContactMobile from './UserContactMobile';
import { analyticsTrack } from 'common/utils/analytics';
import Button from 'common/new-ui/Button';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import {
  EMAIL_UPDATE,
  CONTACT_NUMBER_UPDATE,
  BILLING_LABEL,
  NC_INCREASE_TXN_LIMIT,
  NC_UPDATE_WEBSITE,
  NC_ADD_WEBSITE,
  NC_ADD_ADDITIONAL_WEBSITE,
} from '../deeplink-constants';
import IntoView from 'common/ui/IntoView';
import TextHighlighter from 'common/ui/TextHighlighter';
import InitiateWebsiteChange from './WebsiteSelfServe/InitiateWebsiteChange';
import { FLOWS } from './WebsiteSelfServe/Constants';
import UpdateTransactionLimit from './UpdateTransactionLimit';
import EditWebsiteDetailsModal from 'merchant/views/Account/Profile/components/EditWebsiteDetailsModal';
import { isMobileDevice } from 'merchant/components/Home/data';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import WorkflowStatus from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';

function isWorkflowChangeAllowed(workflow) {
  return (
    !workflow?.loading &&
    (workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(workflow?.workflow_status))
  );
}

function renderWebsites(user, handleEditWebsite, websiteWorkflow) {
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
          <Button.Transparent
            onClick={() => {
              handleEditWebsite(FLOWS.BUSINESS_WEBSITE);
            }}
          >
            <i class="i i-edit p-l" />
          </Button.Transparent>
        )}
    </div>
  );
}

function renderAdditionalWebsites(user, handleEditWebsite, additionalWebsiteWorkflow) {
  const hasAdditionalWebsites = isPresent(user.additional_websites);
  const isLimitReached = hasAdditionalWebsites ? user.additional_websites.length === 5 : false;

  return (
    <div class="website-self-serve__listItem additional-websites__listcontainer">
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
          <span class="additional-websites__listcontainer">--</span>
        )}
      </div>
      {user.business_website &&
        user.isAdditionalDomainWhitelistSelfServeOn &&
        isWorkflowChangeAllowed(additionalWebsiteWorkflow) &&
        (user.role === 'owner' || user.role === 'admin') &&
        !isLimitReached && (
          <div>
            <Button.Transparent
              onClick={() => {
                handleEditWebsite(FLOWS.ADDITIONAL_WEBSITE);
              }}
            >
              <i class="i i-edit p-l" />
            </Button.Transparent>
          </div>
        )}
    </div>
  );
}

const MerchantDetails = ({
  user,
  workflows,
  changeDisplayName,
  changeBillingLabel,
  openModal,
  closeModal,
  tracking,
  fetchWorkflowStatus,
}) => {
  const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
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

  let activationName = 'KYC';
  let trackerName = 'kyc.form_fill';
  if (!user.showInstantActivation || !user.instantActivation.isL1Submitted) {
    activationName = 'Activation';
    trackerName = 'act.form_fill';
  }
  const increaseTxnLimitWorkflow = workflows[WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT];
  const businessWebsiteWorkflow = workflows[WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE];
  const additionalWebsiteWorkflow = workflows[WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE];

  const handleEditWebsite = (flowType) => {
    const hasWebsite = user.has_key_access; // If true => edit website flow; otherwise add flow

    if (hasWebsite) {
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
      });
    }

    // Avoid tracking for additional website flow
    if (flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    let analyticsObject;

    // Edit flow
    if (user.has_key_access) {
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

  const onUpdateTransactionLimitClick = () => {
    openModal({
      size: 'small',
      component: (
        <UpdateTransactionLimit
          onComplete={() => fetchWorkflowStatus(WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT)}
          closeModal={closeModal}
        />
      ),
    });

    // Track when user click on edit limit
    analyticsTrack({
      objectName: 'Transaction limit edit',
      actionName: 'Merchant clicks on edit',
      screen: 'My account screen',
      properties: {
        currentLimit: `${user?.merchant?.max_payment_amount}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const showGenerateTnCModal = (eventName) => {
    openModal({
      size: 'small',
      component: <GenerateTnCPage openModal={openModal} onCloseModal={closeModal} />,
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated(`act.${eventName}`, {
        clickSource: 'My Account',
      }),
    );
    analyticsTrack({
      objectName: `Act ${eventName.replaceAll('_', ' ')}`,
      actionName: 'initiated',
      screen: 'My account screen',
      properties: {
        clickSource: 'My Account',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const labelHandler = (hashedWith, content) => (
    <TextHighlighter hashedWith={hashedWith}>{content}</TextHighlighter>
  );

  const isMerchantAllowedToEditLimit = () => {
    // Unregistered government and gaming merchants aren't allowed to edit transaction limit
    if (
      user.isUnregisteredBusiness &&
      (user.business_category === 'government' || user.business_category === 'gaming')
    )
      return false;

    return true;
  };

  const showTransactionLimitEdit =
    user.role === 'owner' &&
    user.isOrgRZP &&
    user.isTransactionLimitUpdateSelfServeOn &&
    (workflows.increase_transaction_limit?.workflow_exists === false ||
      !['open', 'approved'].includes(workflows.increase_transaction_limit?.workflow_status)) &&
    isMerchantAllowedToEditLimit();

  return (
    <div class="list-group details-row-container">
      <DetailRow label="Contact Name" value={titleCase(user.contact_name)} />

      {changeDisplayName && (
        <DetailRow
          label={() => (
            <div>
              <span>Display Name</span>
              <small class="help-content">
                <i class="i i-info-outline" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div>{ATTR_DETAILS.display_name.desc}</div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
          )}
          value={() =>
            user.display_name ? (
              <span>
                {user.display_name}
                <a
                  class="p-l"
                  onClick={(...args) => {
                    analyticsTrack({
                      objectName: 'dispay name edit',
                      actionName: 'clicked',
                      screen: 'my account',
                      properties: {
                        action: 'reset',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return changeDisplayName(...args);
                  }}
                  title="Edit Display Name"
                >
                  <i className="i i-edit" />
                </a>
              </span>
            ) : (
              <a
                className="p-l"
                onClick={() => {
                  analyticsTrack({
                    objectName: 'display name edit',
                    actionName: 'clicked',
                    screen: 'my account',
                    properties: {
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  return changeDisplayName();
                }}
                title="Set Display Name"
              >
                Set Display Name
              </a>
            )
          }
        />
      )}
      <IntoView hashedWith={EMAIL_UPDATE}>
        <DetailRow
          label={() => labelHandler(EMAIL_UPDATE, 'Contact Email')}
          value={() => (
            <a
              onClick={() => {
                analyticsTrack({
                  objectName: 'contact email',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
              href={`mailto:${user.email}`}
            >
              {user.email}
            </a>
          )}
        />
      </IntoView>
      <IntoView hashedWith={CONTACT_NUMBER_UPDATE}>
        <UserContactMobile />
      </IntoView>

      <DetailRow label="Business Name" value={titleCase(user.business_name)} />

      <DetailRow label="Business Type" value={titleCase(BUSINESS_TYPE_MAP[user.business_type])} />

      <DetailRow
        label="Registration Date"
        value={() => <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />}
      />

      <DetailRow label="Registered By" value={user.marketplace_merchant_name} />

      <ShowWhen additionalCondition={(_user) => _user.isAllowedEdit('activation')}>
        <DetailRow
          label={() => <b>Account Activation</b>}
          value={() => (
            <span>
              <Link
                to={isMobileDevice() ? '/onboarding/steps' : activationUrl}
                onClick={() => {
                  tracking.trackEvent(
                    window.rzpQ.onbr().initiated(trackerName, {
                      clickSource: 'My_Account',
                    }),
                  );
                  analyticsTrack({
                    objectName: 'view KYC form',
                    actionName: 'clicked',
                    screen: 'my account',
                    properties: {
                      status: window.rzp_user.verification.status,
                    },
                  });
                }}
              >
                {user.activated || user.locked || user.submitted
                  ? 'View'
                  : user.activation_progress == 100 && !user.submitted
                  ? 'Submit'
                  : 'Fill'}
                {` ${activationName}`} Form
              </Link>
            </span>
          )}
        />
      </ShowWhen>

      {!!user.activated && (
        <DetailRow
          label="Account Activated On"
          value={() => <Time value={user.activated_at} format="MMM DD YYYY, hh:mm a" />}
        />
      )}

      {!user.showInstantActivation ||
        (user.instantActivation.isL1Submitted && (
          <DetailRow
            label={`${activationName} Form Status`}
            value={() =>
              user.activation_status ? (
                <ActivationStatusLabel status={user.activation_status} />
              ) : (
                <div class="activation-bar-content activation-status-secondary">
                  <div class="activation-bar-text">{user.activation_progress}% Completed</div>
                  <div class="activation-bar">
                    <ProgressBar type="success" max={100} value={user.activation_progress} />
                  </div>
                </div>
              )
            }
          />
        ))}

      {user.isActivated && (
        <React.Fragment>
          <DetailRow
            label="Account Access"
            value={() => (
              <div class="account-access" style={{ textAlign: 'right' }}>
                {user.has_key_access ? 'Complete' : 'Limited'}
                <small class="help-content" style={{ paddingLeft: '4px' }}>
                  <i class="i i-help" />
                  <Popover align="right" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>
                        {user.has_key_access
                          ? 'You have access to all products and API keys. Integrate using our robust APIs or request access to products such as Subscriptions,  Route,  and Smart Collect.'
                          : 'You can only access Payment Links and Invoices. Please provide website/app link to get access to our API’s and other products such as Route, Subscriptions, etc.'}
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            )}
          />

          <IntoView hashedWith={[NC_UPDATE_WEBSITE, NC_ADD_WEBSITE]}>
            <DetailRow
              label={() => (
                <div class="website-self-serve__listItem">
                  <span>Business Website/App details</span>
                  <small class="help-content">
                    <i class="i i-info-outline" />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div>
                          <div>
                            These are the verified websites on which payments can be integrated
                          </div>
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
                        : 'Your request to update the website is under review. We will provide the API keys for the new website once the review is complete.'
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
          </IntoView>
          <IntoView hashedWith={[NC_ADD_ADDITIONAL_WEBSITE]}>
            <DetailRow
              label={() => (
                <div class="website-self-serve__listItem">
                  <span>Additional Business Website/App</span>
                  <small class="help-content">
                    <i class="i i-info-outline" />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div>
                          <div>
                            You can add second website/app to use Razorpay on that website/app
                          </div>
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                  <WorkflowStatus
                    roles={[rolesList.OWNER, rolesList.ADMIN]}
                    workflowType={WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE}
                    reviewStatus="Your request to add the website is under review."
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
          </IntoView>
        </React.Fragment>
      )}

      {changeBillingLabel &&
        user.activation_status == 'activated' &&
        user.business_type != 2 &&
        user.business_type != 11 && (
          <IntoView hashedWith={BILLING_LABEL}>
            <DetailRow
              label={() => (
                <div>
                  <TextHighlighter hashedWith={BILLING_LABEL}>Brand Name</TextHighlighter>
                  <small class="help-content">
                    <i class="i i-info-outline" />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div>
                          <div>Brand Name changes would be reflected in the following places,</div>
                          <div>- Transaction Confirmation Email</div>
                          <div>- Refund Email</div>
                          <div>- Payment Pages</div>
                          <div>- Payment link</div>
                          <div>- Checkout</div>
                          <div>- Smart Collect</div>
                          <div>- Route</div>
                          <div>- Subscriptions</div>
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                </div>
              )}
              value={() =>
                user.billing_label ? (
                  <span>
                    {user.billing_label}
                    <a
                      class="p-l"
                      onClick={(e) => {
                        analyticsTrack({
                          objectName: 'Brand name edit',
                          actionName: 'clicked',
                          screen: 'my account',
                          properties: {
                            currentBrandName: user.billing_label,
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                        changeBillingLabel(e);
                      }}
                      title="Edit Billing Label"
                    >
                      <i class="i i-edit" />
                    </a>
                  </span>
                ) : (
                  <a className="p-l" onClick={changeBillingLabel} title="Set Billing Label">
                    Set Billing Label
                  </a>
                )
              }
            />
          </IntoView>
        )}

      {user.merchant && (
        <IntoView hashedWith={[NC_INCREASE_TXN_LIMIT]}>
          <DetailRow
            label={() => (
              <div class="transaction-limit">
                <span>Limit per transaction</span>
                <small class="help-content">
                  <i class="i i-info-circle" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>The maximum INR limit for only a single transaction.</div>
                    </PopoverBody>
                  </Popover>
                </small>
                <WorkflowStatus
                  roles={[rolesList.OWNER]}
                  workflowType={WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT}
                  reviewStatus="You request to increase to transaction limit has been received. Our team is going through the information provided by you."
                  onReplyClick={() =>
                    openNeedsClarificationModal({
                      workflowType: WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT,
                      workflowName: 'Increase Transaction Limit',
                    })
                  }
                />
              </div>
            )}
            value={() => (
              <div>
                <Amount value={user.merchant.max_payment_amount} currency="INR" />
                {isWorkflowChangeAllowed(increaseTxnLimitWorkflow) && showTransactionLimitEdit && (
                  <Button.Transparent onClick={onUpdateTransactionLimitClick}>
                    <i class="i i-edit p-l" />
                  </Button.Transparent>
                )}
              </div>
            )}
          />
        </IntoView>
      )}

      {user.canGenerateTnCPage && !user.business_website && !user.isAccepted && (
        <DetailRow
          label="Terms and Conditions Page"
          value={() => (
            <div style={{ display: 'flex' }}>
              {!user.merchant_tnc ? (
                <a
                  onClick={() => {
                    const eventName = 'generate_page_now';
                    showGenerateTnCModal(eventName);
                  }}
                >
                  Generate
                </a>
              ) : (
                <>
                  <Button.Secondary
                    onClick={() => {
                      const eventName = 'edit_tnc_page';
                      showGenerateTnCModal(eventName);
                    }}
                    style={{ padding: '3px 8px', fontSize: '12px' }}
                    children="EDIT DETAILS"
                  />
                  <div>
                    <a href={user.merchant_tnc.link} target="_blank" rel="noopener noreferrer">
                      <span>{user.merchant_tnc.link}</span> <i class="i i-external-link" />
                    </a>
                  </div>
                </>
              )}
            </div>
          )}
        />
      )}
    </div>
  );
};

export default connect((state) => ({ workflows: state.workflows }), {
  openModal: fnOpenModal,
  closeModal: fnCloseModal,
  fetchWorkflowStatus: fetchWorkflowStatusReducer,
})(rTracking()(MerchantDetails));
