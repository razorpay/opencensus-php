import { Fragment } from 'react';

import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { ActivationStatusLabel } from 'merchant/components/StatusLabel';

export default props => {
  const { submerchant, isLoading, error, showFullDetails } = props;
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <span>{submerchant.name || 'Default Name'}</span>
            {submerchant.dashboard_access && (
              <div class="btn-toolbar pull-right">
                <button
                  onClick={() => {
                    // TODO: write code for switching user
                  }}
                  class="btn btn-primary btn-sm"
                >
                  Switch Merchant
                </button>
              </div>
            )}
          </div>
          <Alert type="error" message={error} />
          <div class="SliderPanel__Body">
            <div class="panel-body">
              <div class="list-group details-row-container">
                {/* sub-merchant Id */}
                <EntityDetailRow value={submerchant.id} label="Merchant ID" />

                {/* Registered email of sub-merchant */}
                <EntityDetailRow
                  value={submerchant.email}
                  label="Registered Email"
                />

                {/* Creation date of merchant */}
                <EntityDetailRow label="Added On">
                  <Time value={submerchant.created_at} format="LL" />
                </EntityDetailRow>

                {/* Status of Activation */}
                <EntityDetailRow label="Activation Status">
                  {submerchant.details &&
                  submerchant.details.activation_status ? (
                    <ActivationStatusLabel
                      status={submerchant.details.activation_status}
                    />
                  ) : (
                    <span class="label status-label label-warning">
                      Not Submitted
                    </span>
                  )}
                </EntityDetailRow>

                {showFullDetails && (
                  <div class="pair-group-item">
                    {submerchant.user ? (
                      <Fragment>
                        <strong>{submerchant.user.email}</strong> is invited to
                        manage dashboard
                      </Fragment>
                    ) : (
                      <Fragment>
                        <a class="btn-link" onClick={props.onInviteMerchant}>
                          Invite
                        </a>{' '}
                        the merchant to sign up on Razorpay, and manage the
                        account
                      </Fragment>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
