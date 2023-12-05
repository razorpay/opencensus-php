import React, { Fragment, useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { Box, Button, Divider, ClockIcon, Text } from '@razorpay/blade/components';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import AsyncButton from 'react-async-button';
import { useQuery } from '@tanstack/react-query';

import {
  ActivationStatusLabel,
  SubmerchantSettlementLabelNew,
  SubmerchantSettlementLabel,
  XSubmerchantCAStatusLabel,
  CapitalSubMerchantStatusLabel,
} from 'merchant/components/StatusLabel';
import {
  PRODUCT_TYPE,
  NOT_AVAILABLE,
  CAPITAL_STATUS,
} from 'merchant/views/PartnerDashboard/constants';
import withPartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments';

import SubMerchantKycStatusLabel from './SubMerchantKycStatusLabel';
import DetailsAction from './DetailsAction';
import { numberDifferentiation } from 'merchant/views/PartnerDashboard/SubMerchant/utils/index';
import {
  activationStatusMap,
  getActivationStatusBulk,
  filterApplications,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';
import { showNotification } from 'merchant_common/reducers/notifications';
import { CreateBureauLink } from './CreateBureauLink';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { useCountDownTimer } from './useCountDownTimer';
import { fetchBureauLink } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { setItem, removeItem } from 'common/utils/localStorage';
import { UploadBankStatement } from './UploadBankStatement';

const Details = (props) => {
  const {
    submerchant,
    isLoading,
    error,
    onResendInvite,
    product,
    isSubMerchantKycEnabled,
    isReseller,
    getPannelData,
    trackUserEvent,
    isSubMerchantKYCAccess,
    capitalProducts,
    showNotification,
    experiments,
    openModal,
    closeModal,
    user,
  } = props;

  const isPGProduct = product === PRODUCT_TYPE.PG;
  const isXProduct = product === PRODUCT_TYPE.X;
  const isCapitalProduct = product === PRODUCT_TYPE.CAPITAL;
  const contact_mobile = submerchant?.user?.contact_mobile;
  const activation_status = submerchant?.details?.activation_status;
  const smallWrapper = [
    'activated',
    'activated_mcc_pending',
    'under_review',
    'kyc_qualified_unactivated',
    'rejected',
  ].includes(activation_status);

  const isPlatformPartnerWithPGInviteFlow =
    experiments.isPlatformPartnerInviteFlowEnabled && isPGProduct;

  const isShowLargeWrapper =
    (isReseller || isPlatformPartnerWithPGInviteFlow) && isSubMerchantKycEnabled && !smallWrapper;
  const [capitalDetails, setCapitalDetails] = useState();
  const [showMoreDetails, setShowMoreDetails] = useState(false);
  const [isCapitalLoading, setIsCapitalLoading] = useState(false);
  const isCapitalAddress =
    capitalDetails?.company_address_line_1 ||
    capitalDetails?.company_address_line_2 ||
    capitalDetails?.company_address_city ||
    capitalDetails?.company_address_state;

  const isPartnershipCapitalBureauLinkEnabled = experiments.isPartnershipCapitalBureauLinkEnabled;

  const [isUploadBankStatementButtonDisabled, setIsUploadBankStatementButtonDisabled] =
    useState(false);
  // CountDownTimer
  const [showCountDownTimer, setShowCountDownTimer] = useState(false);
  const onTimeOut = () => {
    setShowCountDownTimer(false);
  };
  const { remainingSeconds, startTimer } = useCountDownTimer(30, onTimeOut);
  const handleBladeModalClose = () => {
    removeItem('isCapitalBladeModalOpened');
    closeModal();
  };
  const handleBladeModalOpen = () => {
    setItem('isCapitalBladeModalOpened', true);
  };
  const {
    isLoading: isBureauLinkLoading,
    isFetching,
    refetch: fetchCreateBureauLink,
  } = useQuery({
    queryKey: ['create-bureau-link'],
    queryFn: () => fetchBureauLink(user.id, submerchant.id.replace('acc_', '')),
    refetchOnWindowFocus: false,
    enabled: false,
    onSuccess: (response) => {
      const { data } = response;
      const bureauLinkData = {
        bureauLink: data?.bureau_link || '',
        partnerId: user.id,
        merchantId: submerchant.id.replace('acc_', ''),
        smsCount: data.sms_count || 0,
      };
      setShowCountDownTimer(true);
      startTimer();
      handleBladeModalOpen();
      openModal({
        size: 'med-large',
        component: (
          <CreateBureauLink
            closeModal={handleBladeModalClose}
            bureauLinkData={bureauLinkData}
            showNotification={showNotification}
          />
        ),
      });
    },
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: _err.errors,
      });
    },
  });

  const handleOpenBankStatementUploadModal = () => {
    handleBladeModalOpen();
    const requiredData = {
      partnerId: user.id,
      merchantId: submerchant.id.replace('acc_', ''),
      appId: capitalDetails.id,
    };
    openModal({
      size: 'large',
      component: (
        <UploadBankStatement
          closeModal={handleBladeModalClose}
          uploadData={requiredData}
          showNotification={showNotification}
          isUploadSuccess={() => setIsUploadBankStatementButtonDisabled(true)}
        />
      ),
    });
  };

  const getCapitalData = useCallback(
    (id) => {
      if (capitalProducts?.data?.length > 0) {
        const { data } = capitalProducts;
        const product = data.filter((item) => {
          return item.name === 'LOC_EMI';
        });
        const productId = product[0].id;
        const subMerchantId = [id.replace('acc_', '')];
        getActivationStatusBulk(subMerchantId, productId)
          .then((capitalData) => {
            if (capitalData?.data?.response?.[subMerchantId]?.partner_applications?.length > 0) {
              const {
                data: { response },
              } = capitalData;
              const data = response[subMerchantId].partner_applications;

              setCapitalDetails(filterApplications(data));
              setIsCapitalLoading(false);
            } else {
              setIsCapitalLoading(false);
            }
          })
          .catch(() => {
            setIsCapitalLoading(false);
            showNotification?.({
              type: 'error',
              message: 'There was an error while fetching Application Details',
            });
          });
      } else {
        setIsCapitalLoading(false);
      }
    },
    [capitalProducts, showNotification],
  );

  useEffect(() => {
    if (isCapitalProduct) {
      setIsCapitalLoading(true);
      if (submerchant?.id && !capitalProducts?.loading) {
        getCapitalData(submerchant.id);
      }
    }
  }, [isCapitalProduct, submerchant, capitalProducts, getCapitalData]);

  const handleDetailsToggle = () => {
    setShowMoreDetails((currentValue) => !currentValue);
  };

  const handleNotAvailable = (value) => {
    if (value.toLowerCase() === 'unknown') {
      return NOT_AVAILABLE;
    }
    return value;
  };

  const handleOpenCreateBureauLinkModal = () => {
    fetchCreateBureauLink();
  };
  return (
    <div class={`content-wrapper txn-details ${isShowLargeWrapper ? 'content-lg' : 'content-sm'}`}>
      {isLoading || isCapitalLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel SubmerchantDetail__Panel">
          <div class="panel-heading">
            <div class="submerchant-name">
              {(isReseller || isPlatformPartnerWithPGInviteFlow) &&
              isPGProduct &&
              isSubMerchantKycEnabled
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
                    {(isReseller || isPlatformPartnerWithPGInviteFlow) &&
                    isSubMerchantKycEnabled ? (
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
                    {(isReseller || isPlatformPartnerWithPGInviteFlow) &&
                    isSubMerchantKycEnabled ? (
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

                {(isReseller || isPlatformPartnerWithPGInviteFlow) &&
                  isPGProduct &&
                  isSubMerchantKycEnabled && (
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
                  {capitalDetails && showMoreDetails && (
                    <>
                      {capitalDetails?.id && (
                        <EntityDetailRow value={capitalDetails.id} label="Application ID" />
                      )}
                      {(capitalDetails?.stage || capitalDetails.stage == '') && (
                        <EntityDetailRow label="Activation Status">
                          {capitalDetails?.stage !== '' ? (
                            <CapitalSubMerchantStatusLabel
                              status={activationStatusMap(capitalDetails.stage)}
                            />
                          ) : (
                            <span>Not Available</span>
                          )}
                        </EntityDetailRow>
                      )}
                      {capitalDetails?.business_name ? (
                        <EntityDetailRow
                          value={capitalDetails.business_name}
                          label="Business Name"
                        />
                      ) : null}
                      {capitalDetails?.account_name ? (
                        <EntityDetailRow label="POC Name">
                          {capitalDetails.account_name}
                        </EntityDetailRow>
                      ) : null}
                      {isCapitalAddress ? (
                        <EntityDetailRow label="Company Address">
                          {capitalDetails.company_address_line_1}{' '}
                          {capitalDetails.company_address_line_2}{' '}
                          {capitalDetails.company_address_city},
                          {capitalDetails.company_address_state}
                        </EntityDetailRow>
                      ) : null}

                      {capitalDetails?.company_address_pincode ? (
                        <EntityDetailRow
                          value={capitalDetails.company_address_pincode}
                          label="Company Pincode"
                        />
                      ) : null}

                      {capitalDetails?.business_type ? (
                        <EntityDetailRow
                          value={handleNotAvailable(capitalDetails.business_type)}
                          label="Business Type"
                        />
                      ) : null}
                      {capitalDetails?.business_vintage ? (
                        <EntityDetailRow
                          value={handleNotAvailable(capitalDetails.business_vintage)}
                          label="Business Vintage"
                        />
                      ) : null}
                      {capitalDetails?.annual_turnover_max &&
                      capitalDetails?.annual_turnover_min ? (
                        <EntityDetailRow label="Annual Revenue Slab">
                          {numberDifferentiation(capitalDetails.annual_turnover_min)} -{' '}
                          {numberDifferentiation(capitalDetails.annual_turnover_max)}
                        </EntityDetailRow>
                      ) : null}
                    </>
                  )}
                  {capitalDetails ? (
                    <button className="link_button" onClick={handleDetailsToggle}>
                      {showMoreDetails ? 'show less details' : 'show more details'}
                    </button>
                  ) : null}
                  {isPartnershipCapitalBureauLinkEnabled ? (
                    <Box marginTop="spacing.6">
                      <Divider />
                      <Box marginTop="spacing.7" display="flex">
                        <Button
                          marginRight="spacing.5"
                          onClick={() => {
                            handleOpenCreateBureauLinkModal();
                          }}
                          isDisabled={
                            showCountDownTimer ||
                            capitalDetails?.stage?.toLowerCase() !==
                              CAPITAL_STATUS.bureau_submission
                          }
                          isLoading={isBureauLinkLoading && isFetching}
                          testID="create-bureau-link-btn"
                        >
                          Create Bureau Link
                        </Button>
                        <Button
                          variant="secondary"
                          onClick={() => {
                            handleOpenBankStatementUploadModal();
                          }}
                          isDisabled={
                            isUploadBankStatementButtonDisabled ||
                            capitalDetails?.stage?.toLowerCase() !==
                              CAPITAL_STATUS.income_proof_submission
                          }
                        >
                          Upload bank a/c document
                        </Button>
                      </Box>
                      {showCountDownTimer ? (
                        <Box paddingTop="spacing.2">
                          <Box display="flex" marginTop="spacing.2" alignItems="center">
                            <Box marginRight="spacing.2" display="flex">
                              <ClockIcon size="medium" color="feedback.icon.neutral.lowContrast" />
                            </Box>
                            <Box display="flex">
                              <Text color="surface.text.subdued.lowContrast" textAlign="center">
                                Create link again in
                              </Text>
                              <Text
                                color="feedback.text.notice.lowContrast"
                                textAlign="center"
                                marginLeft="spacing.2"
                              >
                                {remainingSeconds}
                              </Text>
                            </Box>
                          </Box>
                        </Box>
                      ) : null}
                    </Box>
                  ) : null}
                </ShowWhen>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default compose(
  withPartnerDashboardExperiments,
  connect(
    (state) => ({
      user: state.session.user,
    }),
    { showNotification, openModal, closeModal },
  ),
)(Details);
