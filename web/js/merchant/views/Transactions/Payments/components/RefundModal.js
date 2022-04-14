import { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { Link } from 'react-router-dom';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import moment from 'moment';
import {
  rupeesToPaise,
  paiseToRupees,
  titleCase,
  getCommonAnalyticsProperties,
} from 'common/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { compose, bindActionCreators } from 'redux';

export const isPartialPayment = (props) => {
  const refundableAmount = props.payment.amount - props.payment.amount_refunded;
  const amountEntered = rupeesToPaise(props.payable_amount);

  return amountEntered < refundableAmount;
};

export const amountValidation = (props) => {
  const value = props.payable_amount || '';

  if (!value) {
    return 'Amount is required';
  }

  if (value < 1 && props.payment.currency === 'INR') {
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

  return '';
};

export const RefundType = ({ partial, isTitleCase = false }) => {
  let text = partial ? 'partial' : 'full';

  if (isTitleCase) {
    text = titleCase(text);
  }

  return <span>{text}</span>;
};

const selector = formValueSelector('refundModal');

class RefundModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  instant_refund = false;
  constructor(props) {
    super(props);
    this.state = {
      focussed: false,
      reversal: null,
      instantChecked:
        (!showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
          this.props.default_refund_speed == 'optimum') ||
        this.props.payment.gateway_refund_support === false,
      instant_fee: { fee: 0, tax: 0 },
      highlightNote: false,
    };
  }

  analytics = {
    hovered: false,
    hover_breakup: false,
    comment: false,
    check_box: null,
  };

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
      if (this.props.payment.instant_refund_support === true) {
        this.getRefundFee();
      }
    }
  }

  UNSAFE_componentWillMount() {
    const payment = this.props.payment;

    if (this.props.user.isMarketplaceEnabled) {
      this.props.fetchTransfers(payment);
    }

    this.props.initialize({
      comment: '',
      partial: false,
      amount: `${(payment.amount - payment.amount_refunded) / 100} `,
      reverse_all: false,
    });
  }

  componentDidMount() {
    if (this.props.payment && this.props.payment.id) {
      analyticsTrack({
        objectName: 'refund amount popup',
        actionName: 'rendered',
        screen: 'home page',
        properties: {
          paymentId: this.props.payment.id,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    if (this.props.onMount) this.props.onMount(this.props.payment);

    this.props.fetchMerchantBalance();

    if (!this.hasEnoughFunds()) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Add Funds | Default speed ${
          this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
        }`,
      });
    }

    if (
      !(
        this.props.payment.instant_refund_support &&
        this.props.payment.instant_refund_support === true
      )
    ) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Instant Refund not supported | Default speed ${
          this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
        }`,
      });
    }

    if (this.props.transfers.items.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Route transfer | Default speed ${
          this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
        }`,
      });
    }
  }

  componentWillUnmount() {
    if (this.props.onUnmount) this.props.onUnmount(this.props.payment);
  }

  refund(speedValue, props, partial) {
    const payment = this.props.payment;
    const data = {
      amount: rupeesToPaise(props.amount),
      comment: props.comment,
      reverse_all: props.reverse_all ? '1' : '0',
      speed: speedValue,
    };

    if (!partial) {
      data.amount = payment.amount - payment.amount_refunded;
    }
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Yes Refund',
      eventLabel: `${speedValue === 'normal' ? 'Normal' : 'Instant'} Refund | Default speed ${
        this.props.default_refund_speed === 'normal' ? 'Normal' : 'Instant'
      } `,
    });
    analyticsTrack({
      objectName: 'issue refund',
      actionName: 'clicked',
      screen: 'transactions',
    });
    return this.props
      .refundPayment(payment, data)
      .then(() => {
        const default_speed = this.props.default_refund_speed;
        const is_normal = default_speed === 'normal';
        const is_instant = default_speed !== 'normal';
        const is_unchecked = this.analytics.check_box == false;
        const label = `${partial ? 'Partial' : 'Full'} Refund${
          this.analytics.hovered ? ' | Hover Tooltip' : ''
        }${this.analytics.hover_breakup ? ' | Hover Breakup Tooltip' : ''}${
          this.analytics.comment ? ' | Add Comment' : ''
        }${
          this.analytics.check_box !== null
            ? this.analytics.check_box == true
              ? ' | Checked Checkbox'
              : ' | Unchecked Checkbox'
            : ''
        }${default_speed === 'normal' ? ' | Default Speed Normal' : ' | Default Speed Instant'}`;
        if (
          (partial && this.analytics.comment && is_normal) ||
          (partial && this.analytics.comment && is_instant) ||
          (partial && this.analytics.check_box && is_normal) ||
          (partial && this.analytics.hovered && this.analytics.check_box && is_normal) ||
          (partial && is_unchecked && is_instant) ||
          (partial && this.analytics.hover_breakup && is_unchecked && is_instant) ||
          // now instant case
          (!partial && this.analytics.comment && is_normal) ||
          (!partial && this.analytics.comment && is_instant) ||
          (!partial && this.analytics.check_box && is_normal) ||
          (!partial && this.analytics.hovered && this.analytics.check_box && is_normal) ||
          (!partial && is_unchecked && is_instant) ||
          (!partial && this.analytics.hover_breakup && is_unchecked && is_instant)
        ) {
          window.rzpAnalytics?.({
            eventCategory: 'Dashboard - Instant Refund',
            eventAction: `Issue ${partial ? 'Partial' : 'Full'} Refund`,
            eventLabel: label,
          });
        }
        this.props.showNotification({
          type: 'success',
          message: 'Payment refunded',
          closeTimeout: 5000,
        });

        if (typeof this.props.onRefund === 'function') {
          this.props.onRefund();
        }

        if (this.props.afterRefund)
          this.props.afterRefund({
            amount: data.amount,
            partial,
            payment: this.props.payment,
          });

        this.props.closeModal();
      })
      .catch(({ errors }) => {
        if (errors)
          this.props.showNotification({
            type: 'error',
            message: errors,
            closeTimeout: 5000,
          });
      });
  }

  save = (props) => {
    const partial = isPartialPayment(this.props);
    const hasAmountErrors = amountValidation(this.props);

    if (hasAmountErrors) {
      return;
    }

    // For partial refund, if reverse all is checked, we cannot reverse when there is more than 1 transfer on the payment.
    if (partial && props.reverse_all && this.props.transfers.items.length > 1) {
      const errorMsg = `Reversals can't be automated when partially refunding a payment with more than 1 transfer to different linked accounts. Create reversals manually before attempting the refund.`;

      this.props.showNotification({
        type: 'error',
        message: errorMsg,
        closeTimeout: 10000,
      });
      return;
    }
    this.instant_refund = props.instant_refund;
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Click - Issue Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
      speed_requested: props.instant_refund ? 'optimum' : 'normal',
    });
    // If instant_refund is checked
    if ((props.instant_refund || this.state.instantChecked) && Number(props.amount) >= 1) {
      this.props.fetchRefundFee(this.props.payment, rupeesToPaise(props.amount)).then(() => {
        this.context
          .confirm({
            header: 'Do you want to refund this payment?',
            message: () => (
              <div class="confirm-note">
                The payment will be instantly refunded &nbsp;
                <span>
                  <i class="i i-help" />
                  <PopoverComponent
                    theme="dark"
                    align="bottom"
                    parentQuerySelector=".Modal--confirm"
                  >
                    <PopoverBody>
                      <div>
                        If the instant refund is unsuccessful, the fee will be reversed. The payment
                        will still be refunded in 5-7 days.
                      </div>
                    </PopoverBody>
                  </PopoverComponent>
                </span>
              </div>
            ),
            affirmativeLabel: 'Yes, Refund',
            affirmativePendingLabel: 'Refunding...',
            abortLabel: "No, don't!",
            action: () => {
              window.rzpAnalytics?.({
                eventCategory: 'Dashboard - Payments',
                eventAction: 'Refund - Payment',
                eventLabel: `payment_id=${this.props.payment.id}`,
                speed_requested: 'optimum',
              });

              this.refund('optimum', props, partial);
            },
          })
          .catch(() => {
            window.rzpAnalytics?.({
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
            <span class="confirm-note d-block">The payment will be refunded in 5-7 days.</span>
          ),
          affirmativeLabel: 'Yes, Refund',
          affirmativePendingLabel: 'Refunding...',
          abortLabel: "No, don't!",
          action: () => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payments',
              eventAction: 'Refund - Payment',
              eventLabel: `payment_id=${this.props.payment.id}`,
              speed_requested: 'normal',
            });

            this.refund('normal', props, partial);
          },
        })
        .catch(() => {
          window.rzpAnalytics?.({
            eventCategory: 'Dashboard - Payments',
            eventAction: 'Click - Cancel Refund',
            eventLabel: `payment_id=${this.props.payment.id}`,
            speed_requested: 'normal',
          });
        });
    }
  };

  hasEnoughFunds = () => {
    const { payment, payable_amount, user } = this.props;
    const { data } = this.props.current_balance;

    // if this flag is true and they opt for normal refund, we skip balance check validations
    if (payment.direct_settlement_refund && this.state.instantChecked === false) return true;

    const merchant = user.merchants[user.current] || {};
    const isBalanceSource = merchant.refund_source === 'balance';

    const amount = rupeesToPaise(payable_amount);
    const balance = isBalanceSource ? data.balance : data.refund_credits;

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

  getInstantRefundClassNames = (Val) => {
    if (Val) {
      return 'checkbox instant-refund-disable';
    } else if (this.props.current_balance.loading === true || Val === false) {
      return 'checkbox';
    }

    return '';
  };

  showInstantRefund = (payment, isInstantDisabled, user) => {
    const instant_refund_supported =
      payment.instant_refund_support && payment.instant_refund_support === true;
    const refund_check_disabled = isInstantDisabled || !instant_refund_supported;

    if (!showWhenUtil({ featureEnabled: 'disable_instant_refunds' })) {
      return (
        <div>
          <div
            style={{ marginTop: '20px' }}
            class={`${this.getInstantRefundClassNames(
              refund_check_disabled,
            )} instant-refund-check ${
              this.state.instantChecked && !refund_check_disabled ? 'focussed' : ''
            }`}
          >
            <div class="row">
              <div class="col-xs-8">
                <div
                  class="instant-refund-check-container"
                  onMouseEnter={this.handleHoverIn}
                  onMouseLeave={this.handleHoverOut}
                >
                  <Field
                    name="instant_refund"
                    component="input"
                    type="checkbox"
                    checked={this.state.instantChecked && !refund_check_disabled}
                    disabled={refund_check_disabled}
                    onChange={this.onInstantRefundCheckboxClick}
                  />
                  <strong>Refund Instantly</strong>
                </div>
              </div>
              <div class="col-xs-4 text-right">
                {!this.state.instantChecked ? (
                  <span>
                    {!refund_check_disabled ? (
                      <Fragment>
                        <i
                          class="i i-help"
                          onMouseEnter={() => {
                            this.analytics.hovered = true;
                          }}
                        />
                        <PopoverComponent
                          theme="dark"
                          align="bottom"
                          parentQuerySelector=".Modal--small"
                        >
                          <PopoverBody>
                            You can refund this payment instantly for a small fee of{' '}
                            <Amount
                              value={this.state.instant_fee.fee - this.state.instant_fee.tax}
                              currency={payment.currency}
                            />{' '}
                            (Plus Taxes){' '}
                          </PopoverBody>
                        </PopoverComponent>
                      </Fragment>
                    ) : null}
                  </span>
                ) : (
                  !refund_check_disabled && (
                    <Fragment>
                      <Amount
                        parentQuerySelector=".Modal--small"
                        value={this.state.instant_fee.fee}
                        currency={payment.currency}
                      />{' '}
                      <span style={{ marginLeft: '3px' }} class="grey">
                        Fee
                      </span>
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
                  <div class={`low-funds ${this.shouldFormBeOpaque() ? `make-opaque` : null}`}>
                    Your account does not have sufficient balance to instantly refund this payment.
                    &nbsp;{' '}
                    <span>
                      <Link to="/addfunds" target="_blank" rel="noreferrer noopener">
                        Add Funds &nbsp; <i class="i i-external-link" />
                      </Link>
                    </span>
                  </div>
                );
              }
              if (!instant_refund_supported) {
                return (
                  <div class="low-funds">
                    Currently, Instant Refunds are available on TPV, netbanking, UPI and select
                    credit cards and debit cards.
                  </div>
                );
              }

              return null;
            })()
          ) : null}
          {this.state.instantChecked &&
          !refund_check_disabled &&
          !this.props.current_balance.loading ? (
            <div class="low-funds" style={{ marginBottom: 0 }}>
              <div class="instant-refund-breakup-para">
                <div>
                  A total amount of &nbsp;
                  <Amount
                    parentQuerySelector=".Modal--small"
                    value={rupeesToPaise(this.props.payable_amount) + this.state.instant_fee.fee}
                    currency={payment.currency}
                  />
                  &nbsp; will be deducted
                  {user?.isSingleReconEnabled &&
                    user?.isOptimizerEnabled &&
                    payment?.optimizer_provider?.toLowerCase() !== 'razorpay' && (
                      <>
                        {` from your `}
                        <span className="external-gateway-instant-refund">
                          Razorpay Current balance
                        </span>
                      </>
                    )}
                  <div style={{ display: 'inline', marginLeft: '5px' }}>
                    <i
                      onMouseEnter={() => {
                        this.analytics.hover_breakup = true;
                      }}
                      class="i i-info-circle"
                    />
                    <PopoverComponent
                      theme="dark"
                      align="bottom"
                      parentQuerySelector=".Modal--small"
                    >
                      <PopoverBody>
                        <div class="instant-breakup">
                          <div class="flex">
                            <div class="w50 text-left">Refund Amount</div>
                            <div class="w50 text-right">
                              <Amount
                                value={rupeesToPaise(this.props.payable_amount)}
                                currency={payment.currency}
                              />
                            </div>
                          </div>
                          <div class="flex">
                            <div class="w50 text-left">Instant Refund Fees</div>
                            <div class="w50 text-right">
                              +{' '}
                              <Amount
                                value={this.state.instant_fee.fee - this.state.instant_fee.tax}
                                currency={payment.currency}
                              />
                            </div>
                          </div>
                          <div class="flex">
                            <div class="w50 text-left">Taxes</div>
                            <div class="w50 text-right">
                              +
                              <Amount
                                value={this.state.instant_fee.tax}
                                currency={payment.currency}
                              />
                            </div>
                          </div>
                          <hr
                            style={{
                              margin: '5px 0px',
                              opacity: 0.6,
                            }}
                          />
                          <div class="flex">
                            <div style={{ flex: '1 1 auto' }} class="text-left">
                              <b>Amount to be deducted</b>
                            </div>
                            <div style={{ flex: '1 1 auto' }} class="text-right">
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
                    </PopoverComponent>
                  </div>
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
      .fetchRefundFee(this.props.payment, rupeesToPaise(this.props.payable_amount))
      .then((d) => {
        this.setState({ instant_fee: d.data });
      });
  };

  onInstantRefundCheckboxClick = (e) => {
    this.analytics.check_box = e.target.checked;
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: e.target.value ? 'Unchecked - Instant Refund' : 'Checked - Instant Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
    this.setState({ instantChecked: e.target.checked });
  };

  onInstantRefundTooltipHover = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Hover - Instant Refund Tooltip',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
  };

  isPaymentOlderThanSixMonths = () => {
    // created_at is in epoch time
    const { created_at } = this.props.payment;

    // convert both to moment objs
    const createdAt = moment.unix(created_at);
    const today = moment(new Date());

    const monthDiff = today.diff(createdAt, 'months');

    if (monthDiff >= 6) return true;
    else return false;
  };

  isRefundButtonDisabled = () => {
    const { payment } = this.props;
    if (payment.gateway_refund_support === false && payment.instant_refund_support === false)
      return true;
    else return false;
  };

  getMonthsFromDays = (value) => Math.round(moment.duration(value, 'days').asMonths());

  shouldDisableRefundIfUnchecked = () => {
    if (!this.state.instantChecked && this.props.payment.gateway_refund_support === false)
      return true;
    else return false;
  };

  handleHoverIn = (_) => this.setState({ highlightNote: true });

  handleHoverOut = (_) => this.setState({ highlightNote: false });

  shouldFormBeOpaque = (_) => {
    const { payment } = this.props;
    if (payment.gateway_refund_support === false && payment.instant_refund_support === false)
      return true;
    else return false;
  };

  render() {
    const { handleSubmit, payment, transfers, user } = this.props;
    const {
      gateway_refund_support,
      payment_age_limit_for_gateway_refund,
      instant_refund_support,
    } = payment;

    const amountError = amountValidation(this.props);
    const partial = isPartialPayment(this.props);

    const nonFraudDisputeCount =
      payment.disputes &&
      payment.disputes.items.filter((dispute) => dispute.phase !== 'fraud').length;

    const isInstantDisabled = !this.hasEnoughFunds();
    const { highlightNote } = this.state;

    return (
      <div>
        <ModalHeader title="Refund Payment" onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          {nonFraudDisputeCount ? (
            <div class="text-danger m-b">
              There {nonFraudDisputeCount > 1 ? 'are' : 'is'} dispute
              {nonFraudDisputeCount > 1 && 's'} raised against this payment. Kindly check the
              dispute details before initiating a refund.
            </div>
          ) : null}
          <form
            onSubmit={handleSubmit((props) => {
              this.save(props);
            })}
          >
            <div class="refunds-overflow-box">
              {gateway_refund_support === false && (
                <div
                  class={`block-refunds-note ${highlightNote ? `highlight-block-refund-note` : ''}`}
                >
                  <i class="i i-triangle-alert" />{' '}
                  {instant_refund_support ? (
                    <p>
                      This payment was made more than{' '}
                      {this.getMonthsFromDays(payment_age_limit_for_gateway_refund)} months ago, you
                      can only issue instant refund.
                      <a
                        href="https://razorpay.com/docs/payment-gateway/refunds/#handling-errors"
                        rel="noopener noreferrer"
                        target="_blank"
                      >
                        Learn more
                      </a>
                    </p>
                  ) : (
                    <p>
                      This payment was made more than{' '}
                      {this.getMonthsFromDays(payment_age_limit_for_gateway_refund)} months ago,
                      refund not supported
                      <a
                        href="https://razorpay.com/docs/payment-gateway/refunds/#handling-errors"
                        rel="noopener noreferrer"
                        target="_blank"
                      >
                        Learn more
                      </a>
                    </p>
                  )}
                </div>
              )}
              <div class={`form-group ${this.shouldFormBeOpaque() ? `make-opaque` : null}`}>
                <label class="label-required">Refund Amount</label>
                <div class="input-group">
                  <AmountTooltip
                    currency={payment.currency}
                    parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
                    customClass={`input-group-addon ${this.state.focussed ? 'focussed' : ''} ${
                      amountError ? 'error' : ''
                    }`}
                  />
                  <Field
                    name="amount"
                    component={InputField}
                    onFocus={() => {
                      const focussed = this.state.focussed;
                      this.setState({ focussed: !focussed });
                    }}
                    onBlur={() => {
                      const focussed = this.state.focussed;
                      this.setState({ focussed: !focussed });
                    }}
                    class="form-control refund-amt-input"
                    type="number"
                    step="0.01"
                    placeholder="Enter the refund amount"
                  />
                </div>
                {!!amountError ? (
                  <div class="InputField__ErrorText text-danger">{amountError}</div>
                ) : (
                  <small class="help-block">
                    This will be a{' '}
                    <b>
                      <RefundType partial={partial} /> refund
                    </b>
                    .{!partial && <span>&nbsp; Change amount for a partial refund.</span>}
                  </small>
                )}
              </div>
              {transfers.items.length > 0 && (
                <div class="row">
                  <div class="col-xs-12">
                    <div
                      class={`instant-refund-check ${this.state.reversal ? 'focussed' : ''}`}
                      style={{ marginBottom: '0', marginTop: '0' }}
                    >
                      <div
                        style={{ paddingLeft: '5px' }}
                        class="instant-refund-check-container route-transfer-check-container"
                      >
                        <Field
                          name="reverse_all"
                          id="reverse_all"
                          component="input"
                          class="pointer route-transfer-check"
                          type="checkbox"
                          onChange={(e) => this.setState({ reversal: e.target.checked })}
                        />
                        <strong class="icon i-check" for="reverse_all">
                          Reverse all{' '}
                          <a
                            href="https://razorpay.com/docs/route/operations/#reversals"
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            Route Transfers
                          </a>{' '}
                          as well
                        </strong>
                      </div>
                    </div>
                  </div>
                </div>
              )}
              {this.showInstantRefund(payment, isInstantDisabled, user)}
            </div>

            <div
              class={`form-group add-comment-div ${
                this.shouldFormBeOpaque() ? `make-opaque` : null
              }`}
            >
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
            <div
              class="Modal__actions"
              onMouseEnter={this.handleHoverIn}
              onMouseLeave={this.handleHoverOut}
            >
              <button
                class="btn btn-primary btn-block"
                disabled={this.isRefundButtonDisabled() || this.shouldDisableRefundIfUnchecked()}
              >
                Issue <RefundType partial={partial} isTitleCase={true} /> refund
              </button>
            </div>
          </form>
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
      fetchTransfers,
      ...NotificationsActions,
    },
    dispatch,
  );

export default compose(
  reduxForm({
    form: 'refundModal',
  }),
  connect(mapStateToProps, mapDispatchToProps),
)(RefundModal);
