import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose, bindActionCreators } from 'redux';
import { formValueSelector } from 'redux-form';

import Amount from 'common/ui/Amount';
import ModalHeader from 'common/ui/ModalHeader';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { updateConfig } from 'merchant/reducers/config';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { closeModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const raiseTicket = () => {
  CreateTicketEmitter.emit('create-ticket', 'tickets');
};

const selector = formValueSelector('refundModal');
class EnableInstantRefundsModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor(props) {
    super(props);
    this.state = {
      show_breakup: false,
    };
    this.analytics = { learn_more: false };
  }

  enableInstantRefunds = () => {
    selfServeTrackInitiate({
      selfServeAction: `Enable ${this.props.speed === 'normal' ? 'normal' : 'instant'} refund`,
      page: 'Config',
      screen: 'Settings',
    });
    analyticsTrack({
      objectName: `enable ${this.props.speed === 'normal' ? 'normal' : 'instant'} refund popup`,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        actionName: `enable ${this.props.speed === 'normal' ? 'normal' : 'instant'} refund`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    let label;
    this.props
      .updateConfig({
        default_refund_speed: this.props.speed,
      })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: `${
            this.props.speed === 'normal' ? 'Normal' : 'Instant'
          } Refunds Activated Successfully`,
        });
        label = `${
          this.props.speed !== 'normal'
            ? this.props.pricing.custom_pricing
              ? 'Custom Pricing'
              : 'Normal Pricing'
            : ''
        }${this.state.show_breakup ? ' | Show Pricing' : ''}${
          this.analytics.learn_more
            ? ` ${this.props.speed !== 'normal' ? ' | ' : ''} Learn More`
            : ''
        }${
          this.props.speed === 'normal'
            ? ` ${
                this.props.speed !== 'normal' ||
                this.state.show_breakup ||
                this.analytics.learn_more
                  ? '|'
                  : ''
              } Enable Normal Refund`
            : ' | Enable Instant Refund'
        }`;
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Instant Refund',
          eventAction: `Enable ${this.props.speed === 'normal' ? 'Normal' : 'Instant'} Refund`,
          eventLabel: label,
        });
        if (this.props.speed === 'normal') {
          this.props.tracking.trackEvent(
            window.rzpQ.merchantActions().initiated(`Click - Enable Normal Refund`, {
              label: `Normal Refund Modal`,
              session_id: window.session_id,
              category: 'Merchant Dashboard - IR',
            }),
          );
        } else {
          this.props.tracking.trackEvent(
            window.rzpQ.merchantActions().initiated(`Click - Enable Instant Refund`, {
              label: `Instant Refund Modal - ${
                this.props.pricing.custom_pricing ? 'Custom Pricing' : 'Normal Pricing'
              }`,
              session_id: window.session_id,
              category: 'Merchant Dashboard - IR',
            }),
          );
        }
        selfServeTrackSuccess({
          selfServeAction: `Enable ${this.props.speed === 'normal' ? 'normal' : 'instant'} refund`,
          page: 'Config',
          screen: 'Settings',
        });
        analyticsTrack({
          objectName: `${this.props.speed === 'normal' ? 'normal' : 'instant'} refund`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });

        this.props.updated();
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
        analyticsTrack({
          objectName: `${this.props.speed === 'normal' ? 'normal' : 'instant'} refund`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Failure',
            failureReason: errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  render() {
    const rules = this.props.pricing.rules;

    return (
      <div className="enable-instant-refund-modal">
        <ModalHeader
          onCloseClick={() => {
            if (this.props.speed === 'normal') {
              this.props.tracking.trackEvent(
                window.rzpQ.merchantActions().initiated(`Click - Cancel`, {
                  label: `Normal Refund Modal`,
                  session_id: window.session_id,
                  category: 'Merchant Dashboard - IR',
                }),
              );
            } else {
              this.props.tracking.trackEvent(
                window.rzpQ.merchantActions().initiated(`Click - Cancel`, {
                  label: `Instant Refund Modal - ${
                    this.props.pricing.custom_pricing ? 'Custom Pricing' : 'Normal Pricing'
                  }`,
                  session_id: window.session_id,
                  category: 'Merchant Dashboard - IR',
                }),
              );
            }
            analyticsTrack({
              objectName: `enable ${
                this.props.speed === 'normal' ? 'normal' : 'instant'
              } refund popup`,
              actionName: 'clicked',
              screen: 'settings',
              properties: {
                location: 'configuration',
                actionName: 'close',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
          title={
            <div>
              {this.props.speed !== 'normal' ? <i className="i i-instant-refund" /> : null} Enable{' '}
              {this.props.speed === 'normal' ? 'Normal' : 'Instant'} Refund
            </div>
          }
        />
        <div className="modal-body" style={{ paddingBottom: 0 }}>
          <div>
            <div style={{ margin: '10px 0' }} className="change-default-refund-speed">
              <div>
                {this.props.speed == 'normal' ? (
                  <p>
                    Your customers will receive their refunds in 5-7 days. The default refund speed
                    for all your refunds will be set to `normal`
                  </p>
                ) : (
                  <p>
                    Your customers will receive refunds instantly. The default refund speed for all
                    your refunds will be set to `optimum`{' '}
                  </p>
                )}
                &nbsp;
                {this.props.speed !== 'normal' ? (
                  <div className="panel panel-default refund-fee-structure">
                    <div className="panel-heading">
                      <div className="flex">
                        <div style={{ color: '#515978', width: '60%' }}>
                          Minimal fee on each refund
                        </div>
                        <div className="w50 text-right" style={{ width: '40%' }}>
                          <span
                            onClick={() => {
                              const show_breakup = this.state.show_breakup;
                              this.setState({ show_breakup: !show_breakup });
                              if (!show_breakup) {
                                this.props.tracking.trackEvent(
                                  window.rzpQ.merchantActions().initiated(`Click - Show Pricing`, {
                                    label: `Instant Refund Modal - ${
                                      this.props.pricing.custom_pricing
                                        ? 'Custom Pricing'
                                        : 'Normal Pricing'
                                    }`,
                                    category: 'Merchant Dashboard - IR',
                                    session_id: window.session_id,
                                  }),
                                );
                              }
                            }}
                            className="show-fee-struct"
                          >
                            <b>{this.state.show_breakup ? 'Hide' : 'Show'} Pricing</b>{' '}
                            <i
                              className={`i action-arrow i-chevron-${
                                this.state.show_breakup ? 'up' : 'down'
                              }`}
                            >
                              {' '}
                            </i>{' '}
                          </span>
                        </div>
                      </div>
                    </div>
                    {this.state.show_breakup ? (
                      <div className="panel-body" style={{ paddingBottom: '8px' }}>
                        {!this.props.pricing.custom_pricing ? (
                          <div className="instant-breakup">
                            <div className="flex">
                              <div
                                style={{ marginBottom: '5px' }}
                                className="w50 text-left t-heading"
                              >
                                Refund Amount
                              </div>
                              <div
                                style={{ marginBottom: '5px' }}
                                className="w50 text-right t-heading"
                              >
                                Processing Fees
                              </div>
                            </div>
                            {rules.map((r, i) => (
                              <div key={i} className="flex">
                                <div className="text-left amt" style={{ flexGrow: 1 }}>
                                  ₹{' '}
                                  {i > 0 ? r.amount_range_min / 100 + 1 : r.amount_range_min / 100}{' '}
                                  {i == rules.length - 1 ? 'and' : '-'}{' '}
                                  {i == rules.length - 1 ? `above` : r.amount_range_max / 100}{' '}
                                </div>
                                <div className="text-right" style={{ flexGrow: 1 }}>
                                  <Amount
                                    value={r.fixed_rate}
                                    currency="INR"
                                    parentQuerySelector=".Modal--small"
                                  />
                                </div>
                              </div>
                            ))}
                          </div>
                        ) : (
                          <div className="flex">
                            <div style={{ color: '#515978' }}>
                              To know your pricing, please{' '}
                              <a>
                                <strong
                                  className="pointer"
                                  onClick={() => {
                                    window.rzpAnalytics?.({
                                      eventCategory: 'Dashboard - Instant Refund',
                                      eventAction: 'Contact Support',
                                      eventLabel: `Fee Modal | Contact Support`,
                                    });
                                    raiseTicket();
                                  }}
                                  style={{ color: '#0B70E7' }}
                                >
                                  contact support
                                </strong>
                              </a>
                            </div>
                          </div>
                        )}
                      </div>
                    ) : null}
                  </div>
                ) : null}
              </div>
            </div>
          </div>
          <div className="confirm-note-info">
            {this.props.speed == 'normal' ? (
              <div>
                You can still issue instant refunds either from the Dashboard or using the refund
                API. To know more,{' '}
                <a
                  href="https://razorpay.com/docs/payment-gateway/refunds/#using-the-dashboard"
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={() => {
                    selfServeTrackInitiate({
                      selfServeAction: 'Enable Normal Refund',
                      page: 'Config',
                      screen: 'Settings',
                    });
                    window.rzpAnalytics?.({
                      eventCategory: 'Dashboard - Instant Refund',
                      eventAction: `Enable Normal Refund`,
                      eventLabel: `Learn More | Enable Normal Refund`,
                    });
                    this.props.tracking.trackEvent(
                      window.rzpQ.merchantActions().initiated(`Click - Click Here`, {
                        label: `Normal Refund Modal`,
                        session_id: window.session_id,
                        category: 'Merchant Dashboard - IR',
                      }),
                    );
                    this.analytics.learn_more = true;
                  }}
                >
                  <strong className="pointer" style={{ color: '#0B70E7' }}>
                    &nbsp; click here
                  </strong>
                </a>
              </div>
            ) : (
              <div>
                You can still issue normal refunds either from the Dashboard or using the refund
                API. To know more,{' '}
                <a
                  href="https://razorpay.com/docs/payment-gateway/refunds/#using-the-dashboard"
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={() => {
                    window.rzpAnalytics?.({
                      eventCategory: 'Dashboard - Instant Refund',
                      eventAction: `Enable Instant Refund`,
                      eventLabel: `Learn More | Enable Instant Refund`,
                    });

                    this.props.tracking.trackEvent(
                      window.rzpQ.merchantActions().initiated(`Click - Click Here`, {
                        label: `Instant Refund Modal - ${
                          this.props.pricing.custom_pricing ? 'Custom Pricing' : 'Normal Pricing'
                        }`,
                        session_id: window.session_id,
                        category: 'Merchant Dashboard - IR',
                      }),
                    );

                    this.analytics.learn_more = true;
                  }}
                >
                  <strong className="pointer" style={{ color: '#0B70E7' }}>
                    {' '}
                    &nbsp; click here
                  </strong>
                </a>
              </div>
            )}
          </div>
        </div>
        <div>
          <div className="Modal__actions" style={{ padding: '20px', paddingTop: 0 }}>
            <div className="row flex" style={{ marginBottom: '5px' }}>
              <div className="w100 change-default-speed-btn">
                <button className="btn btn-primary btn-block" onClick={this.enableInstantRefunds}>
                  Enable {this.props.speed == 'normal' ? 'Normal' : 'Instant'} Refund
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  const partial = selector(state, 'partial');
  const payable_amount = selector(state, 'amount');
  return {
    ...state.session,
    ...state.payment,
    user: state.session.user,
    transfers: state.payment.transfers,
    default_refund_speed: state.config.config.default_refund_speed,
    partial,
    payable_amount,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      refundPayment,
      fetchPayment,
      fetchRefunds,
      updateConfig,
      fetchTransfers,
      ...NotificationsActions,
    },
    dispatch,
  );

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('EnableInstantRefundsModal')),
)(EnableInstantRefundsModal);
