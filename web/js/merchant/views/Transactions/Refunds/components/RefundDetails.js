import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { Fragment } from 'react';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import RefundStatusTimeline from 'merchant/views/Transactions/Refunds/components/RefundTimeline';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import * as PaymentActions from 'merchant/reducers/payments/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import EnableInstantRefundsModal from 'merchant/views/Transactions/Payments/components/EnableInstantRefundsModal';
import SettlementInfo from '../../../Settlements/components/SettlementInfo';
import Definition from 'common/ui/Definition';

@withRouter
@connect(
  (state) => {
    return {
      ...state.payment,
      user: state.session.user,
      default_refund_speed: state.config.config.default_refund_speed,
      config: state.config.config,
    };
  },
  {
    ...ModalActions,
    ...PaymentActions,
    ...NotificationsActions,
  },
)
export default class PaymentDetailsContainer extends Component {
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
              Refund Id: <b>{this.props.refund.id}</b>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={this.props.statusMsg.type} message={this.props.statusMsg.message} />
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Payment"
                    value={() => (
                      <Link to={`/payments/${this.props.refund.payment_id}`}>
                        <code>{this.props.refund.payment_id}</code>
                      </Link>
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <ContentToggler onToggleClick={this.props.viewRefundHistory}>
                        <span>View History</span>
                        <RefundStatusTimeline refund={this.props.refund} />
                      </ContentToggler>
                    )}
                  />
                  <EntityDetailRow
                    label="Amount"
                    value={() => (
                      <Amount
                        value={this.props.refund.amount}
                        currency={this.props.refund.currency}
                      />
                    )}
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
                          GST - <Amount value={refund.tax} currency={'INR'} />
                        </span>
                      </Definition>
                    </EntityDetailRow>
                  )}

                  <EntityDetailRow
                    label="Refund Speed"
                    value={() => {
                      return (
                        <Fragment>
                          <Fragment>
                            <span>
                              {this.props.refund.speed_processed === 'instant' ||
                              this.props.refund.speed_processed === null ? (
                                <i style={{ fontSize: '18px' }} class="i i-instant-refund" />
                              ) : null}{' '}
                              {this.props.refund.speed_processed !== null
                                ? this.props.refund.speed_processed.charAt(0).toUpperCase() +
                                  this.props.refund.speed_processed.slice(1)
                                : 'Instant'}
                            </span>
                            {/* {!showWhenUtil({
                              featureEnabled: 'disable_instant_refunds',
                            }) &&
                            (this.props.refund.speed_processed === 'instant' ||
                              this.props.refund.speed_processed === null) &&
                            this.props.default_refund_speed == 'normal' ? (
                              <div
                                class="confirm-note-info"
                                style={{
                                  fontSize: '20px',
                                  paddingTop: '10px',
                                  paddingBottom: '10px',
                                }}
                              >
                                <p
                                  style={{
                                    fontSize: '15px',
                                    marginTop: '5px',
                                  }}
                                >
                                  Process all refunds Instantly
                                </p>
                                <Link to={`/config#instantrefunds`}>
                                  <button
                                    onClick={this.enableInstantRefunds}
                                    style={{ marginTop: '15px' }}
                                    class="btn btn-outline"
                                  >
                                    Enable Now
                                    <i class="i i-chevron-right" />
                                  </button>
                                </Link>
                              </div>
                            ) : null} */}
                          </Fragment>
                        </Fragment>
                      );
                    }}
                  />

                  <EntityDetailRow label="Currency" value={this.props.refund.currency} />

                  <EntityDetailRow
                    label="Created At"
                    value={() => (
                      <Time value={this.props.refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                    )}
                  />

                  {this.props.refund.transaction && this.props.user.isUxRevampPhase2Enabled && (
                    <EntityDetailRow label="Settlement Details">
                      <SettlementInfo data={this.props.refund} />
                    </EntityDetailRow>
                  )}

                  <NestedEntityDetailRow
                    label="Acquirer Data"
                    value={this.props.refund.acquirer_data}
                  />
                  <NestedEntityDetailRow label="Notes" value={this.props.refund.notes} />
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
