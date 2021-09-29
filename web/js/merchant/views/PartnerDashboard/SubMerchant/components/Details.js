import React, { Fragment } from 'react';
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
  XSubmerchantCAStatusLabel,
  XSubmerchantVAStatusLabel,
} from 'merchant/components/StatusLabel';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

export default (props) => {
  const { submerchant, isLoading, error, onResendInvite, product } = props;
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel SubmerchantDetail__Panel">
          <div class="panel-heading">
            <div class="submerchant-name">{submerchant.name || 'Default Name'}</div>
            <ShowWhen additionalCondition={(user) => !user.isPartner('pure_platform')}>
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
                <EntityDetailRow value={submerchant.email} label="Registered Email" />

                {/* Creation date of merchant */}
                <EntityDetailRow label="Added On">
                  <Time value={submerchant.created_at} format="LL" />
                </EntityDetailRow>

                <ShowWhen additionalCondition={() => product === PRODUCT_TYPE.PG}>
                  {/* Status of Activation */}
                  <EntityDetailRow label="Activation Status">
                    {submerchant.details && submerchant.details.activation_status ? (
                      <ActivationStatusLabel status={submerchant.details.activation_status} />
                    ) : (
                      <span class="label status-label label-warning">Not Submitted</span>
                    )}
                  </EntityDetailRow>

                  {/* Status of Settlement */}
                  <EntityDetailRow label="Settlement Status">
                    <SubmerchantSettlementLabel
                      status={
                        submerchant.details &&
                        submerchant.details.activation_status === 'activated' &&
                        submerchant.hold_funds === false
                          ? 'active'
                          : 'inactive'
                      }
                    />
                  </EntityDetailRow>
                </ShowWhen>

                <ShowWhen additionalCondition={() => product === PRODUCT_TYPE.X}>
                  <EntityDetailRow label="Virtual Account Status">
                    <XSubmerchantVAStatusLabel
                      status={
                        submerchant.banking_account && submerchant.banking_account.va_status
                          ? submerchant.banking_account.va_status.toLowerCase()
                          : 'inactive'
                      }
                    />
                  </EntityDetailRow>

                  <EntityDetailRow label="Current Account Status">
                    <XSubmerchantCAStatusLabel
                      status={
                        submerchant.banking_account && submerchant.banking_account.ca_status
                          ? submerchant.banking_account.ca_status.toLowerCase()
                          : 'inactive'
                      }
                    />
                  </EntityDetailRow>
                </ShowWhen>

                {/* application details for pure platform partners */}
                {submerchant.application && (
                  <EntityDetailRow label="Application Id">
                    <Link to={`/submerchants/applications/${submerchant.application.id}`}>
                      {submerchant.application.id}
                    </Link>
                  </EntityDetailRow>
                )}

                <ShowWhen
                  myRole="owner admin manager"
                  additionalCondition={(user) =>
                    user.isPartner('aggregator') && product === PRODUCT_TYPE.PG
                  }
                >
                  <div class="pair-group-item">
                    {submerchant.user ? (
                      <Fragment>
                        <strong>{submerchant.user.email}</strong> is managing the dashboard for this
                        account
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
