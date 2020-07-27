import { Fragment } from 'react';
import { Link } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import AsyncButton from 'react-async-button';

import {
  ActivationStatusLabel,
  SubmerchantSettlementLabel,
} from 'merchant/components/StatusLabel';

export default props => {
  const { submerchant, isLoading, error, onResendInvite } = props;
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
            <ShowWhen
              additionalCondition={user => !user.isPartner('pure_platform')}
            >
              {submerchant.user && (
                <div class="btn-toolbar pull-right">
                  <AsyncButton
                    text="Resend Invite"
                    pendingText="Sending..."
                    class="btn btn-primary btn-sm"
                    onClick={onResendInvite}
                  />
                </div>
              )}
            </ShowWhen>
          </div>
          <Alert type="error" message={error} />
          <div class="SliderPanel__Body">
            <div class="panel-body">
              <div class="list-group details-row-container">
                {/* sub-merchant Id */}
                <EntityDetailRow value={submerchant.id} label="Account ID" />

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

                {/* Status of Settlement */}
                <EntityDetailRow label="Settlement Status">
                  <SubmerchantSettlementLabel
                    status={submerchant.hold_funds ? 'inactive' : 'active'}
                  />
                </EntityDetailRow>

                {/* application details for pure platform partners */}
                {submerchant.application && (
                  <EntityDetailRow label="Application Id">
                    <Link
                      to={`/submerchants/applications/${submerchant.application.id}`}
                    >
                      {submerchant.application.id}
                    </Link>
                  </EntityDetailRow>
                )}

                <ShowWhen
                  myRole="owner admin manager"
                  additionalCondition={user => user.isPartner('aggregator')}
                >
                  <div class="pair-group-item">
                    {submerchant.user ? (
                      <Fragment>
                        <strong>{submerchant.user.email}</strong> is managing
                        the dashboard for this account
                      </Fragment>
                    ) : (
                      <Fragment>
                        <a class="btn-link" onClick={props.onInviteMerchant}>
                          Invite
                        </a>{' '}
                        the account to sign up, and manage their dashboard
                      </Fragment>
                    )}
                  </div>
                </ShowWhen>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
