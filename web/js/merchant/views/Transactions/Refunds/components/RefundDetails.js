import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { Component } from 'react';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
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
    if (this.props.refund && this.props.refund.id) {
      analyticsTrack({
        objectName: 'refund details',
        actionName: 'fetched',
        screen: 'transactions',
        properties: {
          location: 'refunds',
          status: 'success',
          ...this.props.refund.analyticsPayload(),
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }

  render() {
    const { refund } = this.props;

    return (
      <div class="content-wrapper content-sm txn-details">
        {this.props.isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              Refund Id: <b>{refund.id}</b>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={this.props.statusMsg.type} message={this.props.statusMsg.message} />
                <div class="list-group details-row-container">
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
                      <ContentToggler onToggleClick={this.props.viewRefundHistory}>
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
                            <i style={{ fontSize: '18px' }} class="i i-instant-refund" />
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

                  <EntityDetailRow label="Processed at">
                    <Time value={refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                  </EntityDetailRow>

                  <EntityDetailRow label="Refund Type" value={refund.refund_type} />

                  {refund.transaction && this.props.user.isUxRevampPhase2Enabled && (
                    <EntityDetailRow label="Settlement Details">
                      <SettlementInfo data={refund} />
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
    window.rzpAnalytics({
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
