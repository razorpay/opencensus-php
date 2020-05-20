import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { Link } from 'react-router-dom';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { Fragment } from 'react';
import { updateConfig } from 'merchant/reducers/config';

import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
} from 'common/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import { showWhenUtil } from 'merchant/components/ShowWhen';
const selector = formValueSelector('refundModal');

@connect(
  state => {
    let partial = selector(state, 'partial');
    let reverse_all = selector(state, 'reverse_all');
    let payable_amount = selector(state, 'amount');
    return {
      ...state.session,
      ...state.payment,
      user: state.session.user,
      transfers: state.payment.transfers,
      default_refund_speed: state.config.config.default_refund_speed,
      partial,
      default_refund_speed: state.config.config.default_refund_speed,
      payable_amount,
    };
  },
  {
    closeModal,
    refundPayment,
    fetchPayment,
    fetchRefunds,
    updateConfig,
    fetchTransfers,
    ...NotificationsActions,
  }
)
export default class EnableInstantRefundsModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
      show_breakup: false,
    };
    this.analytics = { learn_more: false };
  }

  enableInstantRefunds = () => {
    let label;
    if (this.hovered) {
      label = `${this.props.openedFrom} | Hover on Pricing | Yes Enable`;
    } else {
      label = `${this.props.openedFrom} | Didn't hover on Pricing | Yes Enable`;
    }
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
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Instant Refund',
          eventAction: `Enable ${
            this.props.speed === 'normal' ? 'Normal' : 'Instant'
          } Refund`,
          eventLabel: `${
            this.props.pricing.custom_pricing
              ? ' | Custom Pricing'
              : ' | Normal Pricing'
          }${this.state.show_breakup ? ' | Show Pricing' : ''}
            ${this.analytics.learn_more ? ' | Learn More' : ''}
            ${
              this.props.speed === 'normal'
                ? ' | Enable Normal Refund'
                : ' | Enable Instant Refund'
            }`,
        });
        this.props.updated();
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    let rules;
    rules = this.props.pricing.rules;
    return (
      <div class="enable-instant-refund-modal">
        <ModalHeader
          onCloseClick={this.props.closeModal}
          title={
            <div>
              {this.props.speed !== 'normal' ? (
                <i class="i i-instant-refund" />
              ) : null}{' '}
              Enable {this.props.speed === 'normal' ? 'Normal' : 'Instant'}{' '}
              Refund
            </div>
          }
        />
        <div class="modal-body" style={{ paddingBottom: 0 }}>
          <div>
            <React.Fragment>
              <div
                style={{ margin: '10px 0' }}
                class="change-default-refund-speed"
              >
                <div>
                  {this.props.speed == 'normal' ? (
                    <p>
                      Your customers will receive their refunds in 5-7 days. The
                      default refund speed for all your refunds will be set to
                      `normal`
                    </p>
                  ) : (
                    <p>
                      Your customers will receive refunds instantly. The default
                      refund speed for all your refunds will be set to `optimum`{' '}
                    </p>
                  )}
                  &nbsp;
                  {this.props.speed !== 'normal' ? (
                    <div class="panel panel-default refund-fee-structure">
                      <div class="panel-heading">
                        <div class="flex">
                          <div style={{ color: '#515978', width: '60%' }}>
                            Minimal fee on each refund
                          </div>
                          <div class="w50 text-right" style={{ width: '40%' }}>
                            <span
                              onClick={() => {
                                const show_breakup = this.state.show_breakup;
                                this.setState({ show_breakup: !show_breakup });
                              }}
                              class="show-fee-struct"
                            >
                              <b>
                                {this.state.show_breakup ? 'Hide' : 'Show'}{' '}
                                Pricing
                              </b>{' '}
                              <i
                                class={`i action-arrow i-chevron-${
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
                        <Fragment>
                          <div
                            class="panel-body"
                            style={{ paddingBottom: '8px' }}
                          >
                            {!this.props.pricing.custom_pricing ? (
                              <div class="instant-breakup">
                                <div class="flex">
                                  <div
                                    style={{ marginBottom: '5px' }}
                                    class="w50 text-left t-heading"
                                  >
                                    Refund Amount
                                  </div>
                                  <div
                                    style={{ marginBottom: '5px' }}
                                    class="w50 text-right t-heading"
                                  >
                                    Processing Fees
                                  </div>
                                </div>
                                {rules.map((r, i) => (
                                  <div key={i} class="flex">
                                    <div
                                      class="text-left amt"
                                      style={{ flexGrow: 1 }}
                                    >
                                      ₹{' '}
                                      {i > 0
                                        ? r.amount_range_min / 100 + 1
                                        : r.amount_range_min / 100}{' '}
                                      {i == rules.length - 1 ? 'and' : '-'}{' '}
                                      {i == rules.length - 1
                                        ? `above`
                                        : r.amount_range_max / 100}{' '}
                                    </div>
                                    <div
                                      class="text-right"
                                      style={{ flexGrow: 1 }}
                                    >
                                      <Amount
                                        value={r.fixed_rate}
                                        currency={'INR'}
                                        parentQuerySelector={`.Modal--small`}
                                      />
                                    </div>
                                  </div>
                                ))}
                              </div>
                            ) : (
                              <div class="flex">
                                <div style={{ color: '#515978' }}>
                                  To know your pricing, please{' '}
                                  <a>
                                    <strong
                                      class="pointer"
                                      onClick={() => {
                                        window.rzpAnalytics({
                                          eventCategory:
                                            'Dashboard - Instant Refund',
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
                          {/* <div
                            class="panel-footer grey text-center"
                            style={{
                              background: '#fff',
                              padding: '10px 0',
                              fontSize: '13px',
                            }}
                          >
                           We charge{' '}
                            <Amount
                              value={1000}
                              currency={'INR'}
                              parentQuerySelector={`.Modal--small`}
                            />{' '}
                            fees for Debit Card refunds
                          </div> */}
                        </Fragment>
                      ) : null}
                    </div>
                  ) : null}
                </div>
              </div>
            </React.Fragment>
          </div>
          <div class="confirm-note-info">
            {this.props.speed == 'normal' ? (
              <div>
                You can still issue instant refunds either from the Dashboard or
                using the refund API. To know more,{' '}
                <a
                  href="https://razorpay.com/docs/payment-gateway/refunds/#using-the-dashboard"
                  target="_blank"
                >
                  <strong
                    onClick={() => (this.analytics.learn_more = true)}
                    class="pointer"
                    style={{ color: '#0B70E7' }}
                  >
                    &nbsp; click here
                  </strong>
                </a>
              </div>
            ) : (
              <div>
                You can still issue normal refunds either from the Dashboard or
                using the refund API. To know more,{' '}
                <a
                  href="https://razorpay.com/docs/payment-gateway/refunds/#using-the-dashboard"
                  target="_blank"
                >
                  <strong
                    onClick={() => (this.analytics.learn_more = true)}
                    class="pointer"
                    style={{ color: '#0B70E7' }}
                  >
                    {' '}
                    &nbsp; click here
                  </strong>
                </a>
              </div>
            )}
          </div>
        </div>
        <div>
          <div
            class="Modal__actions"
            style={{ padding: '20px', paddingTop: 0 }}
          >
            <div class="row flex" style={{ marginBottom: '5px' }}>
              <div class="w100" class="change-default-speed-btn">
                <button
                  class="btn btn-primary btn-block"
                  onClick={this.enableInstantRefunds}
                >
                  Enable {this.props.speed == 'normal' ? 'Normal' : 'Instant'}{' '}
                  Refund
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const raiseTicket = () => {
  if (window.rzpTicketSystem) {
    const rzpTicketSystem = window.rzpTicketSystem;
    rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
    rzpTicketSystem.openModal('#ticket');
    setTimeout(() => {
      rzpTicketSystem.modal.next();
    }, 0);
    setTimeout(() => {
      var el = document.getElementsByName('request-description')[0];
      el.value =
        'Hello Team,\n' +
        'I’d like to know my custom pricing for instant refunds.';
      el.focus();
    }, 1000);
  }
};
