import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from '../DetailRow';
import ProgressBar from 'rzp/ui/ProgressBar';
import Popover, { PopoverTitle, PopoverBody } from 'rzp/ui/Popover';
import { openModal, closeModal } from 'rzp/modules/modals';

import { ActivationStatusLabel } from 'merchant/components/StatusLabel';

import EditWebsiteDetails from 'merchant/containers/EditWebsiteDetails';

export default connect(null, { openModal, closeModal })(
  ({ user, openModal, closeModal }) => {
    return (
      <div class="list-group details-row-container">
        <DetailRow label="Merchant Name" value={titleCase(user.name)} />

        <DetailRow
          label="Merchant Email"
          value={() => <a href={`mailto:${user.email}`}>{user.email}</a>}
        />

        <DetailRow
          label="Registration Date"
          value={() => (
            <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
          )}
        />

        <DetailRow
          label={() => <b>Account Activation</b>}
          value={() => (
            <span>
              <Link to={'/activation'}>
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
                Activation Form
              </Link>
            </span>
          )}
        />

        {!!user.activated && (
          <DetailRow
            label="Account Activated On"
            value={() => (
              <Time value={user.activated_at} format="MMM DD YYYY, hh:mm a" />
            )}
          />
        )}

        <DetailRow
          label="Activation Form Status"
          value={() =>
            user.activation_status ? (
              <ActivationStatusLabel status={user.activation_status} />
            ) : (
              <div className="activation-bar-content activation-status-secondary">
                <div className="activation-bar-text">
                  {user.activation_progress}% Completed
                </div>
                <div className="activation-bar">
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

        {user.isActivated && (
          <React.Fragment>
            <DetailRow
              label="Account Access"
              value={() => (
                <div class="account-access" style={{ textAlign: 'right' }}>
                  {user.has_key_access ? 'Complete' : 'Limited'}
                  <small
                    className="help-content"
                    style={{ paddingLeft: '4px' }}
                  >
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
              label="Business Website/App details"
              value={() =>
                !user.has_key_access ? (
                  !user.business_website ? (
                    <span>
                      <a
                        onClick={() =>
                          openModal({
                            size: 'small',
                            component: (
                              <EditWebsiteDetails onClose={closeModal} />
                            ),
                          })
                        }
                      >
                        Add Website/App URL for Full Access
                      </a>
                    </span>
                  ) : (
                    <span class="status-label label label-info">
                      Under Review
                    </span>
                  )
                ) : (
                  <a
                    href={user.business_website}
                    target="_blank"
                    rel="noopener"
                  >
                    {user.business_website}
                  </a>
                )
              }
            />
          </React.Fragment>
        )}
      </div>
    );
  }
);
