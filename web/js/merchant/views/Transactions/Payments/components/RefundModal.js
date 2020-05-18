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

export const isPartialPayment = props => {
  const refundableAmount = props.payment.amount - props.payment.amount_refunded,
    amountEntered = rupeesToPaise(props.payable_amount);

  return amountEntered < refundableAmount;
};

export const amountValidation = props => {
  const value = props.payable_amount || '';

  if (!value) {
    return 'Amount is required';
  }

  if (value < 1) {
    return `Amount can't be less than 1`;
  }

  if (isNaN(value) || (value.toString().split('.')[1] || []).length > 2) {
    return 'Amount can only be a Number with atmost 2 decimal places.';
  }
  if (value < 0) {
    return `Amount can't be negative.`;
  }

  const refundableAmount = props.payment.amount - props.payment.amount_refunded;

  if (rupeesToPaise(value) > refundableAmount) {
    return (
      `Amount can't be greater than the total Refundable` +
      ` Amount (${paiseToRupees(refundableAmount)}).`
    );
  }
};

export const RefundType = ({ partial, isTitleCase = false }) => {
  let text = partial ? 'partial' : 'full';

  if (isTitleCase) {
    text = titleCase(text);
  }

  return <span>{text}</span>;
};

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
      payable_amount,
    };
  },
  {
    closeModal,
    refundPayment,
    fetchPayment,
    fetchRefunds,
    fetchTransfers,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'refundModal',
})
export default class RefundModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  instant_refund = false;
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
      instantChecked:
        !showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
        this.props.default_refund_speed == 'optimum',
      instant_fee: {},
    };
  }

  componentDidUpdate(prevProps) {
    const current_payable_amount = rupeesToPaise(this.props.payable_amount);
    const prev_payable_amount = rupeesToPaise(prevProps.payable_amount);
    if (
      current_payable_amount &&
      current_payable_amount !== prev_payable_amount &&
      current_payable_amount >= 100 &&
      !showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
      // this.hasEnoughFunds() && // check if this fine??
      current_payable_amount <= this.props.payment.amount
    ) {
      this.getRefundFee();
    }
  }

  componentWillMount() {
    let payment = this.props.payment;

    if (this.props.user.isMarketplaceEnabled) {
      this.props.fetchTransfers(payment);
    }

    this.props.initialize({
      comment: '',
      partial: false,
      amount: (payment.amount - payment.amount_refunded) / 100 + '',
      reverse_all: false,
    });
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.payment);
    this.props.fetchMerchantBalance();
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.payment);
  }

  refund(speedValue, props, partial) {
    let payment = this.props.payment;
    let data = {
      amount: rupeesToPaise(props.amount),
      comment: props.comment,
      reverse_all: props.reverse_all ? '1' : '0',
      speed: speedValue,
    };

    if (!partial) {
      data.amount = payment.amount - payment.amount_refunded;
    }
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Yes Refund',
      eventLabel: `${
        speedValue === 'normal' ? 'Normal' : 'Instant'
      } Refund | Default speed ${
        this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
      } `,
    });

    if (
      !(
        payment.instant_refund_support &&
        payment.instant_refund_support === true
      )
    ) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Yes Enable',
        eventLabel: `Instant Refund not supported | Default speed ${
          this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
        }`,
      });
    }
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: `Issue ${partial ? 'Partial' : 'Full'} Refund`,
      eventLabel: `${partial ? 'Partial' : 'Full'} Refund${
        this.analytics.hovered ? ' | Over Tooltip' : ''
      }${this.analytics.comment ? ' | Add Comment' : ''}${
        this.instant_refund || this.state.instantChecked
          ? ' | Check Checkbox'
          : ' | Uncheck Checkbox'
      }`,
    });

    return this.props
      .refundPayment(payment, data)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Payment refunded',
          closeTimeout: 5000,
        });

        if (typeof this.props.onRefund === 'function') {
          this.props.onRefund();
        }

        this.props.afterRefund &&
          this.props.afterRefund({
            amount: data.amount,
            partial: partial,
            payment: this.props.payment,
          });

        this.props.closeModal();
      })
      .catch(({ errors }) => {
        errors &&
          this.props.showNotification({
            type: 'error',
            message: errors,
            closeTimeout: 5000,
          });
      });
  }

  save = props => {
    const partial = isPartialPayment(this.props),
      hasAmountErrors = amountValidation(this.props);

    if (hasAmountErrors) {
      return;
    }

    // For partial refund, if reverse all is checked, we cannot reverse when there is more than 1 transfer on the payment.
    if (partial && props.reverse_all && this.props.transfers.items.length > 1) {
      var errorMsg = `Reversals can't be automated when partially refunding a payment with more than 1 transfer to different linked accounts. Create reversals manually before attempting the refund.`;

      this.props.showNotification({
        type: 'error',
        message: errorMsg,
        closeTimeout: 10000,
      });
      return;
    }
    this.instant_refund = props.instant_refund;
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Click - Issue Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
      speed_requested: props.instant_refund ? 'optimum' : 'normal',
    });
    // If instant_refund is checked
    if (
      (props.instant_refund || this.state.instantChecked) &&
      Number(props.amount) >= 1
    ) {
      this.props
        .fetchRefundFee(this.props.payment, props.amount * 100)
        .then(() => {
          this.context
            .confirm({
              header: 'Do you want to refund this payment?',
              message: () => (
                <React.Fragment>
                  <div class="confirm-note">
                    The payment will be instantly refunded &nbsp;
                    <span>
                      <i class="i i-help" />
                      <Popover
                        onMouseOver={() => (this.analytics.hovered = true)}
                        theme="dark"
                        align="bottom"
                        parentQuerySelector={`.Modal--confirm`}
                      >
                        <PopoverBody>
                          <div>
                            If the instant refund is unsuccessful, the fee will
                            be reversed. The payment will still be refunded in
                            5-7 days.
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  </div>
                </React.Fragment>
              ),
              affirmativeLabel: 'Yes, Refund',
              affirmativePendingLabel: 'Refunding...',
              abortLabel: "No, don't!",
              action: () => {
                window.rzpAnalytics({
                  eventCategory: 'Dashboard - Payments',
                  eventAction: 'Refund - Payment',
                  eventLabel: `payment_id=${this.props.payment.id}`,
                  speed_requested: 'optimum',
                });

                this.refund('optimum', props, partial);
              },
            })
            .catch(() => {
              window.rzpAnalytics({
                eventCategory: 'Dashboard - Payments',
                eventAction: 'Click - Cancel Refund',
                eventLabel: `payment_id=${this.props.payment.id}`,
                speed_requested: 'optimum',
              });
            });
        });
    } else {
      this.context
        .confirm({
          header: 'Are you sure you want to refund this payment?',
          message: props.reverse_all ? (
            'Reversals will be automatically created for all transfers on this payment, before the refund'
          ) : (
            <span class="confirm-note d-block">
              The payment will be refunded in 5-7 days.
            </span>
          ),
          affirmativeLabel: 'Yes, Refund',
          affirmativePendingLabel: 'Refunding...',
          abortLabel: "No, don't!",
          action: () => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payments',
              eventAction: 'Refund - Payment',
              eventLabel: `payment_id=${this.props.payment.id}`,
              speed_requested: 'normal',
            });

            this.refund('normal', props, partial);
          },
        })
        .catch(() => {
          window.rzpAnalytics({
            eventCategory: 'Dashboard - Payments',
            eventAction: 'Click - Cancel Refund',
            eventLabel: `payment_id=${this.props.payment.id}`,
            speed_requested: 'normal',
          });
        });
    }
  };

  hasEnoughFunds = () => {
    const { payment, payable_amount } = this.props;
    const { data } = this.props.current_balance;

    // let amount = payment.amount;
    let amount = rupeesToPaise(payable_amount);
    let balance = data.balance;

    if (this.props.current_balance.loading === true) {
      return true;
    }

    if (balance) {
      if (amount > balance) {
        return false;
      } else {
        return true;
      }
    } else {
      return false;
    }
  };

  getInstantRefundClassNames = Val => {
    if (Val) {
      return 'checkbox instant-refund-disable';
    } else if (this.props.current_balance.loading === true || Val === false) {
      return 'checkbox';
    }
  };
  analytics = {
    hovered: false,
    comment: false,
  };
  showInstantRefund = (payment, isInstantDisabled) => {
    const instant_refund_supported =
      payment.instant_refund_support && payment.instant_refund_support === true;
    const refund_check_disabled =
      isInstantDisabled || !instant_refund_supported;
    if (
      !showWhenUtil({ featureEnabled: 'disable_instant_refunds' })
      // &&
      // payment.instant_refund_support &&
      // payment.instant_refund_support === true
    ) {
      return (
        <div>
          <div
            class={
              this.getInstantRefundClassNames(refund_check_disabled) +
              ' instand-refund-check'
            }
          >
            <div class="row">
              <div class="col-xs-8">
                <div class="instant-refund-check-container">
                  <Field
                    name="instant_refund"
                    component="input"
                    type="checkbox"
                    checked={
                      this.state.instantChecked && !refund_check_disabled
                    }
                    disabled={refund_check_disabled}
                    onChange={this.onInstantRefundCheckboxClick}
                  />
                  <strong>Refund Instantly</strong>
                </div>
              </div>
              <div class="col-xs-4 text-right">
                {!this.state.instantChecked ? (
                  <span>
                    <i class="i i-help" />
                    {!refund_check_disabled ? (
                      <Popover
                        theme="dark"
                        align="bottom"
                        parentQuerySelector={`.Modal--small`}
                      >
                        <PopoverBody>
                          You can refund this payment instantly for a small fee
                          of{' '}
                          <Amount
                            value={
                              this.state.instant_fee.fee -
                              this.state.instant_fee.tax
                            }
                            currency={payment.currency}
                          />{' '}
                          (Plus Taxes){' '}
                        </PopoverBody>
                      </Popover>
                    ) : null}
                  </span>
                ) : (
                  !refund_check_disabled && (
                    <Fragment>
                      <Amount
                        parentQuerySelector={`.Modal--small`}
                        value={this.state.instant_fee.fee}
                        currency={payment.currency}
                      />{' '}
                      <span class="grey">Fee</span>
                    </Fragment>
                  )
                )}
              </div>
            </div>
          </div>
          {this.props.current_balance.loading ? (
            <div>Loading...</div>
          ) : refund_check_disabled ? (
            (() => {
              if (isInstantDisabled) {
                return (
                  <div class="low-funds">
                    Your account does not have sufficient balance to instantly
                    refund this payment.
                    <Link
                      onClick={() => {
                        window.rzpAnalytics({
                          eventCategory: 'Dashboard - Instant Refund',
                          eventAction: 'Issue Refund',
                          eventLabel: `Add Funds | Default speed ${
                            this.props.default_refund_speed === 'normal'
                              ? 'Normal'
                              : 'Instant'
                          }`,
                        });
                      }}
                      to={'/addfunds'}
                      target="_blank"
                    >
                      Add Funds
                      <i class="i i-external-link" />
                    </Link>
                  </div>
                );
              }
              if (!instant_refund_supported) {
                return (
                  <div class="low-funds">
                    Currently, Instant Refunds are available only on TPV,
                    netbanking, UPI and select credit cards.
                  </div>
                );
              }
            })()
          ) : null}
          {this.state.instantChecked &&
          !refund_check_disabled &&
          !this.props.current_balance.loading ? (
            <div class="low-funds">
              <div class="instant-refund-breakup-para">
                <div>
                  A total amount of &nbsp;
                  <Amount
                    parentQuerySelector={`.Modal--small`}
                    value={
                      rupeesToPaise(this.props.payable_amount) +
                      this.state.instant_fee.fee
                    }
                    currency={payment.currency}
                  />
                  &nbsp; will be deducted
                  <React.Fragment>
                    <div style={{ display: 'inline', marginLeft: '5px' }}>
                      <i class="i i-info-circle" />
                      <Popover
                        theme="dark"
                        align="bottom"
                        // onMouseOver={() => this.analytics.hovered = true}
                        parentQuerySelector={`.Modal--small`}
                      >
                        <PopoverBody>
                          <div class="instant-breakup">
                            <div class="flex">
                              <div class="w50 text-left">Total Amount</div>
                              <div class="w50 text-right">
                                <Amount
                                  value={rupeesToPaise(
                                    this.props.payable_amount
                                  )}
                                  currency={payment.currency}
                                />
                              </div>
                            </div>
                            <div class="flex">
                              <div class="w50 text-left">
                                Instant Refund Fees
                              </div>
                              <div class="w50 text-right">
                                +{' '}
                                <Amount
                                  value={
                                    this.state.instant_fee.fee -
                                    this.state.instant_fee.tax
                                  }
                                  currency={payment.currency}
                                />
                              </div>
                            </div>
                            <div class="flex">
                              <div class="w50 text-left">Taxes</div>
                              <div class="w50 text-right">
                                +<Amount
                                  value={this.state.instant_fee.tax}
                                  currency={payment.currency}
                                />
                              </div>
                            </div>
                            <hr
                              style={{
                                margin: 0,
                                margin: '5px 0px',
                                opacity: 0.6,
                              }}
                            />
                            <div class="flex">
                              <div style={{ width: '80%' }} class="text-left">
                                <b>Amount to be deducted</b>
                              </div>
                              <div style={{ width: '20%' }} class="text-right">
                                <b>
                                  {' '}
                                  <Amount
                                    value={
                                      this.state.instant_fee.fee +
                                      rupeesToPaise(this.props.payable_amount)
                                    }
                                    currency={payment.currency}
                                  />
                                </b>
                              </div>
                            </div>
                          </div>
                        </PopoverBody>
                      </Popover>
                    </div>
                  </React.Fragment>
                </div>
              </div>
            </div>
          ) : null}
        </div>
      );
    } else return null;
  };

  getRefundFee = () => {
    return this.props
      .fetchRefundFee(
        this.props.payment,
        rupeesToPaise(this.props.payable_amount)
      )
      .then(d => {
        this.setState({ instant_fee: d.data });
      });
  };

  onInstantRefundCheckboxClick = e => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: e.target.value
        ? 'Unchecked - Instant Refund'
        : 'Checked - Instant Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
    this.setState({ instantChecked: e.target.checked });
  };

  onInstantRefundTooltipHover = e => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Hover - Instant Refund Tooltip',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
  };

  render() {
    const { handleSubmit, payment, transfers, refunds } = this.props;
    const amountError = amountValidation(this.props),
      partial = isPartialPayment(this.props);

    const nonFraudDisputeCount =
      payment.disputes &&
      payment.disputes.items.filter(dispute => dispute.phase !== 'fraud')
        .length;

    let isInstantDisabled = !this.hasEnoughFunds();
    return (
      <div>
        <ModalHeader
          title="Refund Payment"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body">
          {nonFraudDisputeCount ? (
            <div class="text-danger m-b">
              There {nonFraudDisputeCount > 1 ? 'are' : 'is'} dispute{nonFraudDisputeCount >
                1 && 's'}{' '}
              raised against this payment. Kindly check the dispute details
              before initiating a refund.
            </div>
          ) : null}
          <form
            onSubmit={handleSubmit(props => {
              this.save(props);
            })}
          >
            <div class="refunds-overflow-box">
              <div class="form-group">
                <label class="label-required">Refund Amount</label>
                <div class="input-group">
                  <AmountTooltip
                    currency={payment.currency}
                    parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
                    customClass="input-group-addon"
                  />
                  <Field
                    name="amount"
                    component={InputField}
                    class="form-control"
                    type="number"
                    placeholder="Enter the refund amount"
                  />
                </div>
                {!!amountError ? (
                  <div class="InputField__ErrorText text-danger">
                    {amountError}
                  </div>
                ) : (
                  <small class="help-block">
                    This will be a{' '}
                    <b>
                      <RefundType partial={partial} /> refund
                    </b>.
                    {!partial && (
                      <span>&nbsp; Change amount for a partial refund.</span>
                    )}
                  </small>
                )}
              </div>
              {transfers.items.length > 0 && (
                <div class="form-group">
                  <div class="checkbox rzpCheckbox route-transfer-checkbox">
                    <Field
                      name="reverse_all"
                      id="reverse_all"
                      component="input"
                      type="checkbox"
                      class="form-control"
                    />
                    <label class="icon i-check" for="reverse_all">
                      Reverse all{' '}
                      <a
                        href="https://razorpay.com/docs/route/operations/#reversals"
                        target="_blank"
                      >
                        Route Transfers
                      </a>{' '}
                      as well
                    </label>
                  </div>
                </div>
              )}
              {this.showInstantRefund(payment, isInstantDisabled)}
            </div>

            <div class="form-group">
              {this.state.showComments ? (
                <div class="form-group mt20">
                  <Field
                    name="comment"
                    placeholder="Comment Description"
                    component={AutoResizeTextarea}
                    class="form-control"
                  />
                </div>
              ) : (
                <a
                  onClick={() => {
                    this.analytics.comment = true;
                    this.setState({ showComments: true });
                  }}
                  class="comment-link-optional"
                >
                  + Add Comments(Optional)
                </a>
              )}
            </div>
            <div class="Modal__actions">
              <button class="btn btn-primary btn-block">
                Issue <RefundType partial={partial} isTitleCase={true} /> refund
              </button>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
