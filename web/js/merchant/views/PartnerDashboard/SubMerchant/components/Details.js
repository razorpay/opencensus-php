import React, { Fragment, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import AsyncButton from 'react-async-button';

import {
  ActivationStatusLabel,
  SubmerchantSettlementLabelNew,
  SubmerchantSettlementLabel,
  XSubmerchantCAStatusLabel,
  CapitalSubMerchantStatusLabel,
} from 'merchant/components/StatusLabel';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import SubMerchantKycStatusLabel from './SubMerchantKycStatusLabel';
import DetailsAction from './DetailsAction';
import { numberDifferentiation } from 'merchant/views/PartnerDashboard/SubMerchant/utils/index';
import {
  getActivationStatusData,
  activationStatusMap,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';

export default (props) => {
  const {
    submerchant,
    isLoading,
    error,
    onResendInvite,
    product,
    isSubMerchantKycResellerEnabled,
    isReseller,
    getPannelData,
    trackUserEvent,
    isSubMerchantKYCAccess,
  } = props;

  const isPGProduct = product === PRODUCT_TYPE.PG;
  const isXProduct = product === PRODUCT_TYPE.X;
  const isCapitalProduct = product === PRODUCT_TYPE.CAPITAL;
  const contact_mobile = submerchant?.user?.contact_mobile;
  const activation_status = submerchant?.details?.activation_status;
  const smallWrapper = ['activated', 'activated_mcc_pending', 'under_review', 'rejected'].includes(
    activation_status,
  );
  const isShowLargeWrapper = isReseller && isSubMerchantKycResellerEnabled && !smallWrapper;
  const [capitalDetails, setCapitalDetails] = useState();
  const [showMoreDetails, setShowMoreDetails] = useState(false);

  useEffect(() => {
    const activationStatusData = async (id) => {
      const response = await getActivationStatusData(id);
      setCapitalDetails(response);
    };
    if (submerchant?.id) {
      activationStatusData(submerchant.id);
    }
  }, [submerchant]);

  const getBusinessVintage = (vintage) => {
    if (vintage.toLowerCase() === 'business_tenure_unknown') {
      return 'Unknown';
    }
    return vintage;
  };

  const handleDetailsToggle = () => {
    setShowMoreDetails((currentValue) => !currentValue);
  };
  return (
    <div class={`content-wrapper txn-details ${isShowLargeWrapper ? 'content-lg' : 'content-sm'}`}>
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel SubmerchantDetail__Panel">
          <div class="panel-heading">
            <div class="submerchant-name">
              {isReseller && isPGProduct && isSubMerchantKycResellerEnabled
                ? 'REQUEST KYC APPROVAL '
                : submerchant.name || 'Default Name'}
            </div>
            <ShowWhen additionalCondition={(user) => !user.isPartner('pure_platform')}>
              {submerchant.user && (
                <div class="btn-toolbar pull-right">
                  <AsyncButton
                    text="Invite Again"
                    pendingText="Sending Invite..."
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

                {/* Registered contact number of sub-merchant */}
                {contact_mobile && (
                  <EntityDetailRow value={contact_mobile} label="Contact Number" />
                )}

                {/* Creation date of merchant */}
                <EntityDetailRow label="Added On">
                  <Time value={submerchant.created_at} format="LL" />
                </EntityDetailRow>

                <ShowWhen additionalCondition={() => isPGProduct}>
                  {/* Status of Activation */}
                  <EntityDetailRow label="Activation Status">
                    {isReseller && isSubMerchantKycResellerEnabled ? (
                      // New Label from experiment
                      <SubMerchantKycStatusLabel
                        activation_status={submerchant.details.activation_status}
                        kyc_access={submerchant.kyc_access}
                        isSubMerchantKYCAccess={isSubMerchantKYCAccess}
                      />
                    ) : // old ui
                    submerchant.details && submerchant.details.activation_status ? (
                      <ActivationStatusLabel status={submerchant.details.activation_status} />
                    ) : (
                      <span class="label status-label label-warning">Not Submitted</span>
                    )}
                  </EntityDetailRow>

                  {/* Status of Settlement */}
                  <EntityDetailRow label="Settlement Status">
                    {isReseller && isSubMerchantKycResellerEnabled ? (
                      <SubmerchantSettlementLabelNew
                        status={
                          submerchant.details &&
                          submerchant.details.activation_status === 'activated' &&
                          submerchant.hold_funds === false
                            ? 'active'
                            : 'inactive'
                        }
                      />
                    ) : (
                      <SubmerchantSettlementLabel
                        status={
                          submerchant.details &&
                          submerchant.details.activation_status === 'activated' &&
                          submerchant.hold_funds === false
                            ? 'active'
                            : 'inactive'
                        }
                      />
                    )}
                  </EntityDetailRow>
                </ShowWhen>

                <ShowWhen additionalCondition={() => isXProduct}>
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
                <ShowWhen additionalCondition={(user) => user.isPartner('pure_platform')}>
                  {submerchant?.application?.id && (
                    <EntityDetailRow label="Application Id">
                      <Link to={`/partners/applications/${submerchant.application.id}`}>
                        {submerchant.application.id}
                      </Link>
                    </EntityDetailRow>
                  )}
                </ShowWhen>

                <ShowWhen
                  myRole="owner admin manager"
                  additionalCondition={(user) => user.isPartner('aggregator') && isPGProduct}
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

                {isReseller && isPGProduct && isSubMerchantKycResellerEnabled && (
                  <DetailsAction
                    activation_status={submerchant.details.activation_status}
                    kyc_access={submerchant.kyc_access}
                    submerchant={submerchant}
                    getPannelData={getPannelData}
                    trackUserEvent={trackUserEvent}
                    isSubMerchantKYCAccess={isSubMerchantKYCAccess}
                  />
                )}
                <ShowWhen additionalCondition={() => isCapitalProduct}>
                  {submerchant?.application && (
                    <EntityDetailRow value={submerchant.application?.id} label="Application ID" />
                  )}
                  {capitalDetails && showMoreDetails && (
                    <>
                      {(capitalDetails?.stage || capitalDetails.stage == '') && (
                        <EntityDetailRow label="Activation Status">
                          {capitalDetails?.stage !== '' ? (
                            <CapitalSubMerchantStatusLabel
                              status={activationStatusMap(capitalDetails.stage).toLowerCase()}
                            />
                          ) : (
                            <span>Not Available</span>
                          )}
                        </EntityDetailRow>
                      )}
                      {capitalDetails?.business?.legal_name && (
                        <EntityDetailRow
                          value={capitalDetails.business.legal_name}
                          label="Business Name"
                        />
                      )}
                      {capitalDetails?.business?.applicants.length > 0 &&
                        capitalDetails?.business?.applicants[0].kyc && (
                          <EntityDetailRow label="POC Name">
                            {capitalDetails.business.applicants[0].kyc.first_name}{' '}
                            {capitalDetails.business.applicants[0].kyc.second_name}
                          </EntityDetailRow>
                        )}
                      {capitalDetails?.business?.addresses?.length > 0 && (
                        <>
                          <EntityDetailRow label="Company Address">
                            {capitalDetails.business.addresses[0].address_line1}{' '}
                            {capitalDetails.business.addresses[0].address_line2}{' '}
                            {capitalDetails.business.addresses[0].city},
                            {capitalDetails.business.addresses[0].state}
                          </EntityDetailRow>
                          <EntityDetailRow
                            value={capitalDetails.business.addresses[0].pincode}
                            label="Company Pincode"
                          />
                        </>
                      )}
                      {capitalDetails?.business?.deed_type && (
                        <EntityDetailRow
                          value={capitalDetails.business.deed_type}
                          label="Business Type"
                        />
                      )}
                      {capitalDetails?.business?.tenure && (
                        <EntityDetailRow
                          value={getBusinessVintage(capitalDetails.business.tenure)}
                          label="Business Vintage"
                        />
                      )}
                      {capitalDetails?.business?.annual_turnover_max &&
                        capitalDetails?.business?.annual_turnover_min && (
                          <EntityDetailRow label="Annual Revenue Slab">
                            {numberDifferentiation(capitalDetails?.business?.annual_turnover_min)} -{' '}
                            {numberDifferentiation(capitalDetails?.business?.annual_turnover_max)}
                          </EntityDetailRow>
                        )}
                    </>
                  )}
                  {capitalDetails && (
                    <button className="link_button" onClick={handleDetailsToggle}>
                      {showMoreDetails ? 'show less details' : 'show more details'}
                    </button>
                  )}
                </ShowWhen>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
