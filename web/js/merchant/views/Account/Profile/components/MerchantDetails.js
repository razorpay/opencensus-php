import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';

import Time from 'common/ui/Time';
import ProgressBar from 'common/ui/ProgressBar';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';

import {
  titleCase,
  isPresent,
  getFormattedAmount,
  getCommonAnalyticsProperties,
} from 'common/utils/rzp-utils';

import DetailRow from 'merchant/components/DetailRow';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import { BUSINESS_TYPE_MAP, ATTR_DETAILS } from 'merchant/views/Account/constants';

import { openModal, closeModal } from 'merchant_common/reducers/modals';

import EditWebsiteDetailsModal from './EditWebsiteDetailsModal';
import UserContactMobile from './UserContactMobile';
import { analyticsTrack } from 'common/utils/analytics';
import Button from 'common/new-ui/Button';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';

function renderWebsites(user, handleEditWebsite, isWebsiteInWorkflow) {
  let businessWebsite = user.business_website ? (
    <div>
      <a href={user.business_website} target="_blank" rel="noopener">
        {user.business_website}
      </a>
    </div>
  ) : null;

  /*
    has_key_access determines if merchant can generate keys
    Let User enter business_website if has_key_access = false & isWebsiteInWorkflow = false
  */
  if (!user.has_key_access) {
    if (!user.business_website && !isWebsiteInWorkflow) {
      businessWebsite = (
        <span>
          <a onClick={handleEditWebsite}>Add Website/App URL for Full Access</a>
        </span>
      );
    } else {
      businessWebsite = <span class="status-label label label-info">Under Review</span>;
    }
  }

  return (
    <div>
      {businessWebsite}
      {isPresent(user.additional_websites) &&
        user.additional_websites.map((website) => (
          <div>
            <a href={website} target="_blank" rel="noopener">
              {website}
            </a>
          </div>
        ))}
    </div>
  );
}

const MerchantDetails = ({
  user,
  changeDisplayName,
  changeBillingLabel,
  openModal,
  closeModal,
  tracking,
  isWebsiteInWorkflow,
  onWebsiteAdd,
}) => {
  let activationName = 'KYC';
  let trackerName = 'kyc.form_fill';
  if (!user.showInstantActivation || !user.instantActivation.isL1Submitted) {
    activationName = 'Activation';
    trackerName = 'act.form_fill';
  }

  const handleEditWebsite = () => {
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('dash.my_account_actions', {
        action: 'Add_Website_Initiated',
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('dash.add_website', {
        clickSource: 'My_Account',
      }),
    );
    openModal({
      size: 'small',
      component: <EditWebsiteDetailsModal onWebsiteAdd={onWebsiteAdd} onClose={closeModal} />,
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

      <DetailRow
        label="Contact Email"
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

      <UserContactMobile />

      <DetailRow label="Business Name" value={titleCase(user.business_name)} />

      <DetailRow label="Business Type" value={titleCase(BUSINESS_TYPE_MAP[user.business_type])} />

      <DetailRow
        label="Registration Date"
        value={() => <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />}
      />

      <DetailRow label="Registered By" value={user.marketplace_merchant_name} />

      <ShowWhen additionalCondition={(user) => user.isAllowedEdit('activation')}>
        <DetailRow
          label={() => <b>Account Activation</b>}
          value={() => (
            <span>
              <Link
                to={'/activation'}
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
                {do {
                  if (user.activated || user.locked || user.submitted) {
                    ('View');
                  } else if (user.activation_progress == 100 && !user.submitted) {
                    ('Submit');
                  } else {
                    ('Fill');
                  }
                }}{' '}
                {activationName} Form
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
          <DetailRow
            label={() => (
              <div>
                <span>Business Website/App details</span>
                <small class="help-content">
                  <i class="i i-info-outline" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>
                        These are the verified websites on which payments can be integrated.
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            )}
            value={() => renderWebsites(user, handleEditWebsite, isWebsiteInWorkflow)}
          />
        </React.Fragment>
      )}

      {changeBillingLabel &&
        user.activation_status == 'activated' &&
        user.business_type != 2 &&
        user.business_type != 11 && (
          <DetailRow
            label={() => (
              <div>
                <span>Brand Name</span>
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
        )}

      {user.merchant && (
        <DetailRow
          label="Limit per Transaction"
          value={() => (
            <div className="transaction-limit">
              <Amount value={user.merchant.max_payment_amount} currency={'INR'} />
              <small class="help-content">
                <i class="i i-info-circle" />
                <Popover align="right" theme="dark">
                  <PopoverBody>
                    <div>The maximum INR limit for only a single transaction.</div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
          )}
        />
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
                    <a href={user.merchant_tnc.link} target="_blank">
                      <span>{user.merchant_tnc.link}</span> <i class="i i-external-link"></i>
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

export default connect(null, { openModal, closeModal })(RTracking()(MerchantDetails));
