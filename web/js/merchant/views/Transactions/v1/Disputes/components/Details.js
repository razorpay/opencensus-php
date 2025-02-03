import React, { useEffect, useState } from 'react';
import { Tooltip, TooltipInteractiveWrapper } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { analyticsTrack } from 'common/utils/analytics';
import { daysFromToday, getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { DisputeStatusLabel } from 'merchant/components/StatusLabel';
import roleList from 'merchant/helpers/permissions/roles-list';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import { CANNOT_ACCEPT_DISPUTE_TOOLTIP_TEXT } from 'merchant/views/Transactions/v1/Disputes/components/constants';
import {
  isTransactionCleanupEnabled,
  isTransactionsV2Enabled,
} from 'merchant/views/Transactions/v2/common/utils';
import { useNavigate } from 'react-router-dom';

import ConfirmModal from './ConfirmModal';
import ContestDispute from './ContestDispute';
import StatusBanner from './StatusBanner';
import UpdatedBy from './UpdatedBy';

export const daysLeftInExpiry = (expiresOn, prefixForDays = '') => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <span class="text-muted">Passed</span>;
  } else if (daysLeft === 0) {
    return <strong class="text-danger">Today</strong>;
  } else if (daysLeft === 1) {
    return <strong class="text-danger">Tomorrow</strong>;
  } else {
    return `${prefixForDays}${daysLeft} day${daysLeft > 1 ? 's' : ''}`;
  }
};

const DisputeDetails = (props) => {
  const {
    dispute,
    isLoading,
    error,
    onCloseSecView,
    goToLink,
    openModal,
    closeModal,
    showNotification,
    user,
    isAdminAsMerchant,
    fetchIsAdminAsMerchant,
  } = props;
  const navigate = useNavigate();
  const [showContest, setShowContest] = useState(!!dispute?.evidence);
  const contestRef = React.createRef();
  const splitz = useSplitzService();
  const { isDisputePresentmentEnabled, userRole } = props.user;
  const isDisputeOpen = dispute.status === 'open';
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;

  const params = new Proxy(new URLSearchParams(window.location?.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  const initiatePoint = params?.init_point;
  const initiatePage = params?.init_page;
  const screen = initiatePage?.split('.')[0] || 'Disputes';
  const page = initiatePage?.split('.')[1];

  const canUserTakeAction = [
    roleList.OWNER,
    roleList.ADMIN,
    roleList.MANAGER,
    roleList.OPERATIONS,
    roleList.FINANCE,
  ].includes(userRole);

  useEffect(() => {
    if (dispute?.evidence) {
      setShowContest(dispute.evidence?.amount !== 0);
    } else {
      setShowContest(false);
    }
  }, [dispute.evidence, dispute.id]);

  React.useEffect(() => {
    const { loading, error } = isAdminAsMerchant;
    if (loading && error === null) fetchIsAdminAsMerchant();
  }, []);

  const acceptDispute = () => {
    if (canUserTakeAction) {
      openModal({
        size: 'small',
        component: (
          <ConfirmModal
            context="accept"
            closeModal={closeModal}
            dispute={dispute}
            showNotification={showNotification}
            title="Are you sure you want to accept this chargeback?"
            description={
              <>
                <Amount value={dispute.amount} currency={dispute.currency} /> will be immediately
                deducted from your Razorpay account balance
              </>
            }
          />
        ),
      });
    }
  };

  const redirectAndTrackSelfServe = () => {
    if (isTransactionCleanupEnabled()) {
      navigate(
        `payments/${dispute.payment_id}?init_point=${initiatePoint}&init_page=${initiatePage}`,
      );
    } else {
      goToLink(
        `payments/${dispute.payment_id}?init_point=${initiatePoint}&init_page=${initiatePage}`,
      );
    }
    const selfServeInitiateData = {
      selfServeAction: 'Payment Details Fetched',
      page,
      screen,
      version,
      props: {
        initiatePoint,
      },
    };
    if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;

    selfServeTrackInitiate(selfServeInitiateData);
  };

  const contestDispute = () => {
    if (canUserTakeAction) {
      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: 'contest begin',
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
          version,
          disputeId: dispute.id,
          isAdminAsMerchant: isAdminAsMerchant?.data,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      setShowContest(true);
      // Scrolling contest section into view
      setTimeout(() => {
        document.getElementById('contest-dispute').scrollIntoView({ behavior: 'smooth' });
      }, 0);
    }
  };

  return (
    <div class="content-wrapper content-sm txn-details dispute-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {onCloseSecView && (
              <button type="button" class="close close-secondary" onClick={onCloseSecView}>
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
            Dispute Id: <strong>{dispute.id}</strong>
          </div>
          <Alert type="error" message={error} />
          <div class="SliderPanel__Body">
            {!isDisputePresentmentEnabled && isDisputeOpen && (
              <div class="alert alert-warning rzp-banner">
                <div class="rzp-banner-text">
                  {/* text required only for fraud dispute */}
                  {dispute.phase === 'fraud' && (
                    <p>
                      This transaction is suspected to be fraudulent. If you agree, a good practice
                      would be to initiate a refund, in order to prevent a chargeback.
                    </p>
                  )}

                  <p>
                    {/* text depending upon if dispute is fraud or not */}
                    {dispute.phase === 'fraud' ? (
                      'If you think this is a valid transaction, then '
                    ) : (
                      <>
                        A customer has raised a dispute for&nbsp;
                        <Amount value={dispute.amount} currency={dispute.currency} />
                        ,&nbsp;
                      </>
                    )}
                    {/* Text required in all types of dispute  */}
                    kindly respond to the mail sent to you by&nbsp;
                    <Time value={dispute.respond_by} format="ll" />
                    &nbsp; ({daysLeftInExpiry(dispute.respond_by, 'in ')}).
                  </p>

                  {/* text NOT required for fraud dispute */}
                  {dispute.phase !== 'fraud' && (
                    <p>Failing to do so, the disputed amount will be deducted from your account.</p>
                  )}
                </div>
              </div>
            )}

            {isDisputePresentmentEnabled && isDisputeOpen && (
              <div class="alert alert-warning dispute-banner">
                <div class="rzp-banner-text">
                  {dispute.phase === 'fraud' ? (
                    <p>
                      <span>
                        This transaction is reported as fraudulent by the account holder. A good
                        practice would be to stop processing of the order/service and to reverse the
                        transaction. If you believe this is a genuine transaction, we request you to
                        respond before&nbsp;
                      </span>
                      <strong>
                        <Time value={dispute.respond_by} format="ll" />
                      </strong>
                      <span>&nbsp; with corresponding proofs to avoid losing the dispute.</span>
                    </p>
                  ) : dispute.amount_deducted > 0 ? (
                    <p>
                      Your customer has raised a dispute for&nbsp;
                      <Amount value={dispute.amount} currency={dispute.currency} />. As per banking
                      guidelines, we have debited the amount from your Razorpay balance. Kindly
                      respond before <Time value={dispute.respond_by} format="ll" /> and help us
                      represent the case in your favour. If no response is received before the
                      deadline, the dispute will be deemed accepted. Upon winning the dispute, the
                      dispute amount will be added back to your Razorpay balance
                    </p>
                  ) : (
                    <p>
                      Your customer has raised a dispute for&nbsp;
                      <Amount value={dispute.amount} currency={dispute.currency} />
                      .&nbsp;Kindly respond before&nbsp;
                      <strong>
                        {daysFromToday(dispute.respond_by) in [0, 1] ? (
                          daysLeftInExpiry(dispute.respond_by)
                        ) : (
                          <Time value={dispute.respond_by} format="ll" />
                        )}
                      </strong>
                      <span>
                        &nbsp; and help us represent the case in your favour. If no response is
                        received before the deadline, the dispute will be deemed accepted and the
                        amount will be deducted from your Razorpay balance.
                      </span>
                    </p>
                  )}
                </div>
                {daysFromToday(dispute.respond_by) >= 0 && (
                  <div class="dispute-cta">
                    <button
                      class="btn btn-primary"
                      disabled={!canUserTakeAction}
                      onClick={contestDispute}
                    >
                      Contest &amp; upload evidence
                    </button>
                    {dispute?.isBalanceSufficient ||
                    !dispute.hasOwnProperty('isBalanceSufficient') ? (
                      <button
                        class="btn btn-outline"
                        disabled={!canUserTakeAction}
                        onClick={acceptDispute}
                      >
                        Accept Dispute
                      </button>
                    ) : (
                      <Tooltip content={CANNOT_ACCEPT_DISPUTE_TOOLTIP_TEXT}>
                        <TooltipInteractiveWrapper>
                          <button
                            class="btn btn-outline"
                            disabled={!(canUserTakeAction && dispute?.isBalanceSufficient)}
                            onClick={acceptDispute}
                          >
                            Accept Dispute
                          </button>
                        </TooltipInteractiveWrapper>
                      </Tooltip>
                    )}
                  </div>
                )}
              </div>
            )}

            <div class="panel-body">
              <div class="list-group details-row-container">
                {/* disputed amount */}
                <EntityDetailRow label="Dispute amount">
                  <Amount value={dispute.amount} currency={dispute.currency} />
                </EntityDetailRow>

                {/* status of dispute */}
              </div>
              <EntityDetailRow label="Status">
                <DisputeStatusLabel status={dispute.status} />
                {dispute.amount_deducted > 0 && (
                  <div class="alert alert-info status-alert">
                    <div class="rzp-banner-text">
                      <p>
                        <Amount value={dispute.amount_deducted} currency={dispute.currency} /> has
                        been debited from your Razorpay account balance
                      </p>
                    </div>
                  </div>
                )}
              </EntityDetailRow>
              {/* expiry date of dispute */}
              <EntityDetailRow label="Respond By">
                {isDisputeOpen ? (
                  <>
                    <Time value={dispute.respond_by} format="LL" />
                    &nbsp;({daysLeftInExpiry(dispute.respond_by, 'In ')})
                  </>
                ) : (
                  '--'
                )}
              </EntityDetailRow>

              {/* phase of dispute */}
              <EntityDetailRow label="Type" value={titleCase(dispute.phase)} />

              {/* reason_description of dispute */}
              <EntityDetailRow label="Reason" pairClass="reason">
                {dispute.reason_description}
                <ContentToggler show={false}>
                  Learn More
                  <>
                    <strong>
                      {dispute?.reason?.network} •{' '}
                      <span title="gateway code">{dispute?.reason?.gateway_code}</span>
                    </strong>
                    <div>{dispute?.reason?.gateway_description}</div>
                  </>
                </ContentToggler>
              </EntityDetailRow>

              {/* payment */}
              <EntityDetailRow label="Payment">
                <a
                  onClick={() => {
                    redirectAndTrackSelfServe();
                  }}
                >
                  <code>{dispute.payment_id}</code>
                </a>
              </EntityDetailRow>

              {/* created_at of dispute */}
              <EntityDetailRow label="Created At">
                <Time value={dispute.created_at} format="LL / hh:mm A" />
              </EntityDetailRow>

              {/* comment */}
              <EntityDetailRow
                label="Comment"
                value={() =>
                  dispute.comment ||
                  (dispute?.evidence?.amount === 0 && dispute?.evidence?.summary) ||
                  '--'
                }
              />

              {dispute?.lifecycle?.length ? (
                <EntityDetailRow label="Updated by">
                  <UpdatedBy dispute={dispute} />
                </EntityDetailRow>
              ) : (
                ''
              )}

              {isDisputePresentmentEnabled && showContest && (
                <ContestDispute
                  dispute={dispute}
                  ref={contestRef}
                  showNotification={showNotification}
                  onCancelContest={acceptDispute}
                  openModal={openModal}
                  closeModal={closeModal}
                />
              )}
              {isDisputePresentmentEnabled && dispute.status !== 'open' && (
                <StatusBanner dispute={dispute} />
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetchIsAdminAsMerchant }, dispatch);

export default connect(
  (state) => ({ user: state.session.user, isAdminAsMerchant: state.profile.isAdminAsMerchant }),
  mapDispatchToProps,
)(DisputeDetails);
