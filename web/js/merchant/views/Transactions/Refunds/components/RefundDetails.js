import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { Component } from 'react';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { Link, withRouter } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import RefundStatusTimeline from 'merchant/views/Transactions/Refunds/components/RefundTimeline';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { connect } from 'react-redux';
import * as PaymentActions from 'merchant/reducers/payments/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import SettlementInfo from '../../../Settlements/components/SettlementInfo';
import Definition from 'common/ui/Definition';
import { bindActionCreators } from 'redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

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

  render() {
    const { isLoading, statusMsg, viewRefundHistory, refund, user } = this.props;

    return (
      <div className="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              Refund Id: <b>{refund.id}</b>
            </div>

            <div className="SliderPanel__Body">
              <div className="panel-body">
                <Alert type={statusMsg?.type} message={statusMsg?.message} />
                <div className="list-group details-row-container">
                  <EntityDetailRow
                    label="Payment"
                    value={() => (
                      <Link to={`/payments/${refund.payment_id}`}>
                        <code>{refund.payment_id}</code>
                      </Link>
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <ContentToggler onToggleClick={viewRefundHistory}>
                        <span>View History</span>
                        <RefundStatusTimeline refund={refund} />
                      </ContentToggler>
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

                  {refund.transaction && user?.isUxRevampPhase2Enabled && (
                    <EntityDetailRow label="Settlement Details">
                      <SettlementInfo data={refund} entityType="refund" showTimeline />
                    </EntityDetailRow>
                  )}

                  <NestedEntityDetailRow label="Acquirer Data" value={refund.acquirer_data} />
                  <NestedEntityDetailRow label="Notes" value={refund.notes} />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }

  enableInstantRefunds = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Enable Now',
      eventLabel: `Refund detail page | Enable Now`,
    });
  };
}

const mapStateToProps = (state) => {
  return {
    ...state.payment,
    user: state.session.user,
    org: state.session.org,
    default_refund_speed: state.config.config.default_refund_speed,
    config: state.config.config,
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
