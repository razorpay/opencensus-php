import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { Component } from 'react';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import RefundStatusTimeline from 'merchant/views/Transactions/v1/Refunds/components/RefundTimeline';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { connect } from 'react-redux';
import * as PaymentActions from 'merchant/reducers/payments/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Definition from 'common/ui/Definition';
import { bindActionCreators } from 'redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { OptimizerDetails } from 'merchant/views/Transactions/v1/Payments/components/OptimizerDetails';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { SelfServeActionPages } from 'common/constant/enums';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';

class PaymentDetailsContainer extends Component {
  componentDidUpdate() {
    const { refund } = this.props;
    if (refund?.id) {
      analyticsTrack({
        objectName: 'refund details',
        actionName: 'fetched',
        screen: 'transactions',
        properties: {
          location: 'refunds',
          status: 'success',
          ...refund?.analyticsPayload(),
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }

  isFeatureEnabled = () => {
    const { org } = this.props;
    return org?.features?.indexOf('show_refnd_lateauth_param') > -1;
  };

  onPaymentIdClick = () => {
    const selfServeInitiateData = {
      selfServeAction: 'Payment Details Fetched',
      page: 'Refunds',
      screen: 'Transactions',
      props: {
        initiatePoint: 'refund-details',
        sessionId: window?.session_id,
      },
    };
    selfServeTrackInitiate(selfServeInitiateData);
  };

  render() {
    const { isLoading, statusMsg, viewRefundHistory, refund, user, terminalProviders, location } =
      this.props;
    const navigationState = location?.state;
    const { arn, rrn, utr } = refund.acquirer_data ?? {};
    return (
      <div className="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div
            className={`panel panel-default SliderPanel${
              user?.isSingleReconEnabled && user?.isOptimizerEnabled ? ' opt-remove-margin' : ''
            }`}
          >
            <div className="panel-heading">
              Refund Id: <b>{refund.id}</b>
            </div>

            <div className="SliderPanel__Body">
              <div
                className={`panel-body${
                  user?.isSingleReconEnabled && user?.isOptimizerEnabled
                    ? ' optimizer-refund-panel-body'
                    : ''
                }`}
              >
                <Alert type={statusMsg?.type} message={statusMsg?.message} />
                <div
                  className={`list-group details-row-container${
                    user?.isSingleReconEnabled && user?.isOptimizerEnabled
                      ? ' opt-remove-margin'
                      : ''
                  }`}
                >
                  <EntityDetailRow
                    label="Payment"
                    value={() => (
                      <Link
                        to={`/payments/${refund.payment_id}?init_point=refund-details&init_page=${
                          navigationState?.openedFrom
                            ? navigationState.openedFrom
                            : SelfServeActionPages.TransactionsRefunds
                        }`}
                        onClick={this.onPaymentIdClick}
                      >
                        <code>{refund.payment_id}</code>
                      </Link>
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <>
                        <RefundStatusLabel status={refund.status} />
                        <ContentToggler onToggleClick={viewRefundHistory}>
                          <span>View History</span>
                          <RefundStatusTimeline refund={refund} />
                        </ContentToggler>
                      </>
                    )}
                  />
                  <EntityDetailRow
                    label="Amount"
                    value={() => <Amount value={refund.amount} currency={refund.currency} />}
                  />

                  {refund.fees && refund.tax && (
                    <EntityDetailRow label="Total Fee">
                      <Definition>
                        <Amount value={refund.fees} />
                        <span>
                          Instant refund fee -{' '}
                          <Amount value={refund.fees - refund.tax} currency={refund.currency} />
                        </span>
                        <span>
                          GST - <Amount value={refund.tax} currency="INR" />
                        </span>
                      </Definition>
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow label="RRN/ARN" value={arn || rrn || utr} />

                  <EntityDetailRow
                    label="Refund Speed"
                    value={() => {
                      const refundSpeed = refund.speed_processed;
                      return (
                        <span>
                          {refundSpeed === 'instant' || refundSpeed === null ? (
                            <i style={{ fontSize: '18px' }} className="i i-instant-refund" />
                          ) : null}{' '}
                          {/* added check for if speed_processed = undefined */}
                          {refundSpeed !== null
                            ? refundSpeed
                              ? refundSpeed.charAt(0).toUpperCase() + refundSpeed.slice(1)
                              : ''
                            : 'Instant'}
                        </span>
                      );
                    }}
                  />

                  <EntityDetailRow label="Currency" value={refund.currency} />

                  <EntityDetailRow
                    label="Created At"
                    value={() => (
                      <Time value={refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                    )}
                  />

                  <ShowWhen additionalCondition={this.isFeatureEnabled}>
                    <EntityDetailRow label="Refund Type" value={refund.refund_type} />
                    <EntityDetailRow label="Processed at">
                      <Time value={refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                    </EntityDetailRow>
                  </ShowWhen>

                  <NestedEntityDetailRow label="Notes" value={refund.notes} />
                </div>
                {user?.isSingleReconEnabled &&
                  user?.isOptimizerEnabled &&
                  refund?.optimizer_provider && (
                    <OptimizerDetails
                      payment={refund}
                      terminalProviders={terminalProviders}
                      scrolledToBottom={true}
                      page="Refund Detail"
                    />
                  )}
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  const { payment, session, config, navigator } = state;
  return {
    ...payment,
    user: session.user,
    org: session.org,
    default_refund_speed: config.config.default_refund_speed,
    config: config.config,
    terminalProviders: navigator.terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...PaymentActions,
      ...NotificationsActions,
    },
    dispatch,
  );

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(PaymentDetailsContainer));
