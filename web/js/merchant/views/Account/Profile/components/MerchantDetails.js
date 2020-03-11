import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import Time from 'common/ui/Time';
import ProgressBar from 'common/ui/ProgressBar';
import { titleCase, isPresent } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';
import DetailRow from 'merchant/components/DetailRow';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import EditWebsiteDetails from 'merchant/containers/EditWebsiteDetails';

const businessTypeMap = {
  1: 'Proprietorship',
  2: 'Individual',
  3: 'Partnership',
  4: 'Private',
  5: 'Public',
  6: 'LLP',
  7: 'NGO',
  9: 'Trust',
  10: 'Society',
};

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
      businessWebsite = (
        <span class="status-label label label-info">Under Review</span>
      );
    }
  }

  return (
    <div>
      {businessWebsite}
      {isPresent(user.additional_websites) &&
        user.additional_websites.map(website => (
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
  changeContactMobile,
  openModal,
  closeModal,
  tracking,
  isWebsiteInWorkflow,
  onWebsiteAdd,
}) => {
  const activationName =
    !user.showInstantActivation || !user.instantActivation.isL1Submitted
      ? 'Activation'
      : 'KYC';

  const handleEditWebsite = () => {
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('dash.my_account_actions', {
        action: 'Add_Website_Initiated',
      })
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('dash.add_website', {
        clickSource: 'My_Account',
      })
    );
    openModal({
      size: 'small',
      component: (
        <EditWebsiteDetails onWebsiteAdd={onWebsiteAdd} onClose={closeModal} />
      ),
    });
  };

  return (
    <div class="list-group details-row-container">
      <DetailRow label="Contact Name" value={titleCase(user.name)} />

      {changeDisplayName && (
        <DetailRow
          label={() => (
            <div>
              <span>Display Name</span>
              <small class="help-content">
                <i class="i i-info-outline" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div>
                      This is the display name that you and your team will see
                      on the Razorpay dashboard.
                    </div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
          )}
          value={() => (
            <span>
              {user.display_name || 'Set Display Name'}
              <a
                class="p-l"
                title="Edit Display Name"
                onClick={changeDisplayName}
              >
                <i class="i i-edit" />
              </a>
            </span>
          )}
        />
      )}

      <DetailRow
        label="Contact Email"
        value={() => <a href={`mailto:${user.email}`}>{user.email}</a>}
      />

      <DetailRow
        label="Contact Number"
        value={() => (
          <span>
            {user.user.contact_mobile || '--'}
            <ShowWhen
              additionalCondition={user => user.isContactMobileChangeAllowed}
            >
              <a
                class="p-l"
                onClick={changeContactMobile}
                title="Edit contact mobile"
              >
                <i class="i i-edit" />
              </a>
            </ShowWhen>
          </span>
        )}
      />

      <DetailRow label="Business Name" value={titleCase(user.business_name)} />

      <DetailRow
        label="Business Type"
        value={titleCase(businessTypeMap[user.business_type])}
      />

      <DetailRow
        label="Registration Date"
        value={() => (
          <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
        )}
      />

      <DetailRow label="Registered By" value={user.marketplace_merchant_name} />

      <ShowWhen additionalCondition={user => user.isAllowedEdit('activation')}>
        <DetailRow
          label={() => <b>Account Activation</b>}
          value={() => (
            <span>
              <Link
                to={'/activation'}
                onClick={() => {
                  tracking.trackEvent(
                    window.rzpQ.onbr().initiated('kyc.form_fill', {
                      clickSource: 'My_Account',
                    })
                  );
                }}
              >
                {do {
                  if (user.activated || user.locked || user.submitted) {
                    ('View');
                  } else if (
                    user.activation_progress == 100 &&
                    !user.submitted
                  ) {
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
          value={() => (
            <Time value={user.activated_at} format="MMM DD YYYY, hh:mm a" />
          )}
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
                  <div class="activation-bar-text">
                    {user.activation_progress}% Completed
                  </div>
                  <div class="activation-bar">
                    <ProgressBar
                      type="success"
                      max={100}
                      value={user.activation_progress}
                    />
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
                        These are the verified websites on which payments can be
                        integrated.
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            )}
            value={() =>
              renderWebsites(user, handleEditWebsite, isWebsiteInWorkflow)
            }
          />
        </React.Fragment>
      )}
    </div>
  );
};

export default connect(null, { openModal, closeModal })(
  RTracking()(MerchantDetails)
);
