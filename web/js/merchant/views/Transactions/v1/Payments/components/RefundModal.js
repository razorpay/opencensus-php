import { Component, Fragment } from 'react';
import { useQuery } from '@tanstack/react-query';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { compose, bindActionCreators } from 'redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import { withI18Service } from 'common/i18';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import {
  rupeesToPaise,
  titleCase,
  getCommonAnalyticsProperties,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
} from 'common/utils/rzp-utils';
import { validateAmount } from 'common/utils/validators';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  refundPayment,
  refundOfflinePayment,
  updateRefundStatusInNotes,
  voidPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
  fetchEzetapKeys,
} from 'merchant/reducers/payments/details';
import {
  PAYMENT_STATUS,
  FETCH_EZETAP_KEY_NAME,
} from 'merchant/views/Transactions/v1/Payments/constants';
import { trackRefundError } from 'merchant/views/Transactions/v1/Payments/track';
import { closeModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

export const isPartialPayment = (props) => {
  const refundableAmount = props.payment.amount - props.payment.amount_refunded;
  const amountEntered = i18CurrencyConversionFromCommonUnitToMinorUnit(
    props.payable_amount,
    props.payment?.currency,
  );

  return amountEntered < refundableAmount;
};

export const amountValidation = (props) => {
  const value = props.payable_amount || '';
  const currency = props.payment?.currency || 'INR';

  if (!value) {
    return 'Amount is required';
  }

  if (value < 1 && props.payment.currency === 'INR') {
    return `Amount can't be less than 1`;
  }

  const amountError = validateAmount(value, null, currency);

  if (amountError) {
    return amountError;
  }

  const refundableAmount = props.payment.amount - props.payment.amount_refunded;

  if (i18CurrencyConversionFromCommonUnitToMinorUnit(value, currency) > refundableAmount) {
    return (
      `Amount can't be greater than the total Refundable` +
      ` Amount (${i18CurrencyConversionFromMinorUnitToCommonUnit(refundableAmount, currency)}).`
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

function Query(props) {
  return props.children(
    useQuery({ queryKey: [props.keyName], queryFn: props.fn, ...props.options }),
  );
}

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
      refundApiInProgress: false,
    };
  }

  analytics = {
    hovered: false,
    hover_breakup: false,
    comment: false,
    check_box: null,
  };

  componentDidUpdate(prevProps) {
    const { payment, payable_amount } = this.props;
    const current_payable_amount = rupeesToPaise(payable_amount, payment.currency);
    const prev_payable_amount = rupeesToPaise(prevProps.payable_amount, payment.currency);
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

  componentDidMount() {
    const { payment, user, fetchTransfers, fetchMerchantBalance, transfers, onMount, initialize } =
      this.props;

    if (user?.isMarketplaceEnabled) {
      fetchTransfers(payment);
    }

    initialize({
      comment: '',
      partial: false,
      amount: `${i18CurrencyConversionFromMinorUnitToCommonUnit(
        payment?.amount - payment?.amount_refunded,
        payment?.currency,
      )}`,
      reverse_all: false,
    });

    if (payment?.id) {
      analyticsTrack({
        objectName: 'refund amount popup',
        actionName: 'rendered',
        screen: 'home page',
        properties: {
          paymentId: payment.id,
          paymentMethod: payment.method,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    if (onMount) onMount(payment);

    fetchMerchantBalance();

    if (!this.hasEnoughFunds()) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Add Funds | Default speed ${this.getLabelForRefundDefaultSpeed()}`,
      });
    }

    if (!payment?.instant_refund_support) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Instant Refund not supported | Default speed ${this.getLabelForRefundDefaultSpeed()}`,
      });
    }

    if (transfers?.items?.length) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Issue Refund',
        eventLabel: `Route transfer | Default speed ${this.getLabelForRefundDefaultSpeed()}`,
      });
    }
  }

  componentWillUnmount() {
    if (this.props.onUnmount) this.props.onUnmount(this.props.payment);
  }

  refund(speedValue, props, partial, ezetapKey) {
    // to access latest refundApiInProgress state
    this.setState({}, () => {
      if (!this.state.refundApiInProgress) {
        this.setState({ refundApiInProgress: true });
        const payment = this.props.payment;
        const paymentByCardOffline = payment.method === 'card' && payment.receiver_type === 'pos';
        const voidPayment = payment.status === PAYMENT_STATUS.AUTHORIZED;

        let data = {};
        if (paymentByCardOffline) {
          data = {
            appKey: ezetapKey?.appKey,
            username: ezetapKey?.username,
            amount: payment.amount - payment.amount_refunded, // full refund as partial refunds are disabled for offline card transactions
            [voidPayment ? 'txnId' : 'externalRefNumber']: voidPayment
              ? payment?.notes?.txn_id
              : payment?.notes?.external_ref_id1,
          };
        } else {
          data = {
            amount: i18CurrencyConversionFromCommonUnitToMinorUnit(props.amount, payment.currency),
            comment: props.comment,
            reverse_all: props.reverse_all ? '1' : '0',
            speed: speedValue,
          };
        }

        if (!partial) {
          data.amount = payment.amount - payment.amount_refunded;
        }
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Instant Refund',
          eventAction: 'Yes Refund',
          eventLabel: `${
            speedValue === 'normal' ? 'Normal' : 'Instant'
          } Refund | Default speed ${this.getLabelForRefundDefaultSpeed()} `,
        });
        analyticsTrack({
          objectName: 'issue refund',
          actionName: 'clicked',
          screen: 'transactions',
          properties: {
            paymentId: payment.id,
            paymentMethod: payment.method,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });

        const refundPayment = paymentByCardOffline
          ? voidPayment
            ? this.props.voidPayment
            : this.props.refundOfflinePayment
          : this.props.refundPayment;

        refundPayment(payment, data)
          .then((response) => {
            if (paymentByCardOffline && !response?.data?.success) {
              trackRefundError({
                paymentMethod: payment.method,
                paymentId: payment.id,
                error: response?.data?.errorMessage,
                ...getCommonAnalyticsProperties(window.rzp_user),
              });
              this.props.showNotification({
                type: 'error',
                message: response?.data?.errorMessage || 'Something Went Wrong',
                closeTimeout: 5000,
              });
            } else {
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
              }${
                default_speed === 'normal' ? ' | Default Speed Normal' : ' | Default Speed Instant'
              }`;
              /* istanbul ignore next */
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

              /* istanbul ignore else */
              const { updateRefundStatusInNotes, onRefund } = this.props;
              const isOnRefundFunction = typeof onRefund === 'function';
              if (paymentByCardOffline) {
                const promise = updateRefundStatusInNotes(payment, {
                  notes: { ...payment.notes, refund_status: 'processing' },
                });
                Promise.resolve(promise).then(() => {
                  if (isOnRefundFunction) {
                    onRefund();
                  }
                });
              } else if (isOnRefundFunction) {
                onRefund();
              }

              /* istanbul ignore else */
              if (this.props.afterRefund)
                this.props.afterRefund({
                  amount: data.amount,
                  partial,
                  payment: this.props.payment,
                });

              this.props.closeModal();
            }
          })
          .catch(
            /* istanbul ignore next */ ({ errors }) => {
              trackRefundError({
                paymentMethod: payment.method,
                paymentId: payment.id,
                error: Array.isArray(errors) ? errors.join(',') : JSON.stringify(errors),
                ...getCommonAnalyticsProperties(window.rzp_user),
              });
              if (errors)
                this.props.showNotification({
                  type: 'error',
                  message: errors,
                  closeTimeout: 5000,
                });
            },
          )
          .finally(() => {
            this.setState({ refundApiInProgress: false });
          });
      }
    });
  }

  save = (props, ezetapKey) => {
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
      const { currency } = this.props.payment;
      this.props
        .fetchRefundFee(this.props.payment, rupeesToPaise(props.amount, currency))
        .then(() => {
          this.context
            .confirm({
              header: 'Do you want to refund this payment?',
              message: () => (
                <div className="confirm-note">
                  The payment will be instantly refunded &nbsp;
                  <span>
                    <i className="i i-help" />
                    <PopoverComponent
                      theme="dark"
                      align="bottom"
                      parentQuerySelector=".Modal--confirm"
                    >
                      <PopoverBody>
                        <div>
                          If the instant refund is unsuccessful, the fee will be reversed. The
                          payment will still be refunded in 5-7 days.
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
            .catch(
              /* istanbul ignore next */ () => {
                window.rzpAnalytics?.({
                  eventCategory: 'Dashboard - Payments',
                  eventAction: 'Click - Cancel Refund',
                  eventLabel: `payment_id=${this.props.payment.id}`,
                  speed_requested: 'optimum',
                });
              },
            );
        });
    } else {
      this.context
        .confirm({
          header: 'Are you sure you want to refund this payment?',
          message: props.reverse_all ? (
            'Reversals will be automatically created for all transfers on this payment, before the refund'
          ) : (
            <span className="confirm-note d-block">The payment will be refunded in 5-7 days.</span>
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

            this.refund('normal', props, partial, ezetapKey);
          },
        })
        .catch(
          /* istanbul ignore next */ () => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payments',
              eventAction: 'Click - Cancel Refund',
              eventLabel: `payment_id=${this.props.payment.id}`,
              speed_requested: 'normal',
            });
          },
        );
    }
  };

  getLabelForRefundDefaultSpeed = () => {
    const { default_refund_speed } = this.props;
    return default_refund_speed === 'normal' ? 'Normal' : 'Instant';
  };

  hasEnoughFunds = () => {
    const { payment, payable_amount, user, current_balance } = this.props;
    const { instantChecked } = this.state;
    const { data } = current_balance;

    // if this flag is true and they opt for normal refund, we skip balance check validations
    if (payment?.direct_settlement_refund && !instantChecked) return true;

    const merchant = user?.merchants[user.current] || {};
    const isBalanceSource = merchant?.refund_source === 'balance';

    const amount = rupeesToPaise(payable_amount, payment.currency);
    let balance = isBalanceSource ? data?.balance : data?.refund_credits;

    if (user?.isRefundSourceFallbackEnabled) {
      balance = Math.max(data?.balance || 0, data?.refund_credits || 0);
    }

    if (current_balance?.loading) {
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
    /* istanbul ignore else */
    if (Val) {
      return 'checkbox instant-refund-disable';
    } else if (this.props.current_balance.loading === true || Val === false) {
      return 'checkbox';
    }
    /* istanbul ignore next */
    return '';
  };

  showInstantRefund = (payment, isInstantDisabled, user) => {
    const instant_refund_supported =
      payment.instant_refund_support && payment.instant_refund_support === true;
    const refund_check_disabled = isInstantDisabled || !instant_refund_supported;
    const optimierInstantRefundDisabled =
      payment.instant_refund_support === false && user?.isOptimizerEnabled;
    if (
      !optimierInstantRefundDisabled &&
      !showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
      !this.props.i18.isConfigTagEnabled('refunds.instant_refunds')
    ) {
      return (
        <div>
          <div
            style={{ marginTop: '20px' }}
            className={`${this.getInstantRefundClassNames(
              refund_check_disabled,
            )} instant-refund-check ${
              this.state.instantChecked && !refund_check_disabled ? 'focussed' : ''
            }`}
          >
            <div className="row">
              <div className="col-xs-8">
                <div
                  className="instant-refund-check-container"
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
              <div className="col-xs-4 text-right">
                {!this.state.instantChecked ? (
                  <span>
                    {!refund_check_disabled ? (
                      <Fragment>
                        <i
                          className="i i-help"
                          onMouseEnter={
                            /* istanbul ignore next */ () => {
                              this.analytics.hovered = true;
                            }
                          }
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
                      <span style={{ marginLeft: '3px' }} className="grey">
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
                  <div className={`low-funds ${this.shouldFormBeOpaque() ? `make-opaque` : null}`}>
                    Your account does not have sufficient balance to instantly refund this payment.
                    &nbsp;
                    <span>
                      {user.isRefundSourceFallbackEnabled ? (
                        <Link to="/credits" target="_blank" rel="noreferrer noopener">
                          Add Credits &nbsp; <i className="i i-external-link" />
                        </Link>
                      ) : (
                        <Link to="/addfunds" target="_blank" rel="noreferrer noopener">
                          Add Funds &nbsp; <i className="i i-external-link" />
                        </Link>
                      )}
                    </span>
                  </div>
                );
              }
              /* istanbul ignore else */
              if (
                !this.props.i18.isConfigTagEnabled('refunds.instant_refunds') &&
                !instant_refund_supported
              ) {
                return (
                  <div className="low-funds">
                    Currently, Instant Refunds are available on TPV, netbanking and UPI only.
                  </div>
                );
              }
              /* istanbul ignore next */
              return null;
            })()
          ) : null}
          {this.state.instantChecked &&
          !refund_check_disabled &&
          !this.props.current_balance.loading ? (
            <div className="low-funds" style={{ marginBottom: 0 }}>
              <div className="instant-refund-breakup-para">
                <div>
                  A total amount of &nbsp;
                  <Amount
                    parentQuerySelector=".Modal--small"
                    value={
                      rupeesToPaise(this.props.payable_amount, this.props.payment.currency) +
                      this.state.instant_fee.fee
                    }
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
                      onMouseEnter={
                        /* istanbul ignore next */ () => {
                          this.analytics.hover_breakup = true;
                        }
                      }
                      className="i i-info-circle"
                    />
                    <PopoverComponent
                      theme="dark"
                      align="bottom"
                      parentQuerySelector=".Modal--small"
                    >
                      <PopoverBody>
                        <div className="instant-breakup">
                          <div className="flex">
                            <div className="w50 text-left">Refund Amount</div>
                            <div className="w50 text-right">
                              <Amount
                                value={rupeesToPaise(
                                  this.props.payable_amount,
                                  this.props.payment.currency,
                                )}
                                currency={payment.currency}
                              />
                            </div>
                          </div>
                          <div className="flex">
                            <div className="w50 text-left">Instant Refund Fees</div>
                            <div className="w50 text-right">
                              +{' '}
                              <Amount
                                value={this.state.instant_fee.fee - this.state.instant_fee.tax}
                                currency={payment.currency}
                              />
                            </div>
                          </div>
                          <div className="flex">
                            <div className="w50 text-left">Taxes</div>
                            <div className="w50 text-right">
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
                          <div className="flex">
                            <div style={{ flex: '1 1 auto' }} className="text-left">
                              <b>Amount to be deducted</b>
                            </div>
                            <div style={{ flex: '1 1 auto' }} className="text-right">
                              <b>
                                {' '}
                                <Amount
                                  value={
                                    this.state.instant_fee.fee +
                                    rupeesToPaise(
                                      this.props.payable_amount,
                                      this.props.payment.currency,
                                    )
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
      .fetchRefundFee(
        this.props.payment,
        rupeesToPaise(this.props.payable_amount, this.props.payment.currency),
      )
      .then((d) => {
        this.setState({ instant_fee: d.data });
      });
  };

  onInstantRefundCheckboxClick = (e) => {
    this.analytics.check_box = e.target.checked;
    /* istanbul ignore next */
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: e.target.value ? 'Unchecked - Instant Refund' : 'Checked - Instant Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
    this.setState({ instantChecked: e.target.checked });
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
    const { gateway_refund_support, payment_age_limit_for_gateway_refund, instant_refund_support } =
      payment;

    const amountError = amountValidation(this.props);
    const partial = isPartialPayment(this.props);

    const nonFraudDisputeCount =
      payment.disputes &&
      payment.disputes.items.filter((dispute) => dispute.phase !== 'fraud').length;

    const isInstantDisabled = !this.hasEnoughFunds();
    const { highlightNote } = this.state;

    const paymentByCardOffline = payment.method === 'card' && payment.receiver_type === 'pos';
    const onlyFullRefundAllowed = paymentByCardOffline && payment.method === 'card';

    const fetchKeys = async () => {
      if (paymentByCardOffline) {
        const dataPromise = await this.props.fetchEzetapKeys();
        return dataPromise?.data || {};
      }
      return null;
    };
    return (
      <Query
        keyName={FETCH_EZETAP_KEY_NAME}
        fn={fetchKeys}
        options={{
          enabled: false,
          refetchOnWindowFocus: false,
          staleTime: Infinity,
        }}
      >
        {({ data: ezetapData }) => (
          <div>
            <ModalHeader title="Refund Payment" onCloseClick={this.props.closeModal} />
            <div className="modal-body">
              {nonFraudDisputeCount ? (
                <div className="text-danger m-b">
                  There {nonFraudDisputeCount > 1 ? 'are' : 'is'} dispute
                  {nonFraudDisputeCount > 1 && 's'} raised against this payment. Kindly check the
                  dispute details before initiating a refund.
                </div>
              ) : null}
              <form
                onSubmit={handleSubmit((props) => {
                  this.save(props, ezetapData);
                })}
              >
                <div className="refunds-overflow-box">
                  {gateway_refund_support === false && (
                    <div
                      className={`block-refunds-note ${
                        highlightNote ? `highlight-block-refund-note` : ''
                      }`}
                    >
                      <i className="i i-triangle-alert" />{' '}
                      {instant_refund_support ? (
                        <p>
                          This payment was made more than{' '}
                          {this.getMonthsFromDays(payment_age_limit_for_gateway_refund)} months ago,
                          you can only issue instant refund.
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
                  <div className={`form-group ${this.shouldFormBeOpaque() ? `make-opaque` : null}`}>
                    <label className="label-required">Refund Amount</label>
                    <div className="input-group">
                      <AmountTooltip
                        currency={payment.currency}
                        parentQuerySelector=".ReactModal__Overlay .ReactModal__Content"
                        customClass={`input-group-addon ${this.state.focussed ? 'focussed' : ''} ${
                          amountError ? 'error' : ''
                        }`}
                      />
                      <Field
                        data-testid="refund-amount"
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
                        className="form-control refund-amt-input"
                        type="number"
                        step="0.01"
                        placeholder="Enter the refund amount"
                        readOnly={onlyFullRefundAllowed}
                      />
                    </div>
                    {!!amountError ? (
                      <div className="InputField__ErrorText text-danger">{amountError}</div>
                    ) : (
                      <small className="help-block">
                        This will be a{' '}
                        <b>
                          <RefundType partial={partial} /> refund
                        </b>
                        .{!partial && <span>&nbsp; Change amount for a partial refund.</span>}
                      </small>
                    )}
                  </div>
                  {transfers.items.length > 0 && (
                    <div className="row">
                      <div className="col-xs-12">
                        <div
                          className={`instant-refund-check ${
                            this.state.reversal ? 'focussed' : ''
                          }`}
                          style={{ marginBottom: '0', marginTop: '0' }}
                        >
                          <div
                            style={{ paddingLeft: '5px' }}
                            className="instant-refund-check-container route-transfer-check-container"
                          >
                            <Field
                              name="reverse_all"
                              id="reverse_all"
                              component="input"
                              className="pointer route-transfer-check"
                              type="checkbox"
                              onChange={(e) => this.setState({ reversal: e.target.checked })}
                            />
                            <strong className="icon i-check" htmlFor="reverse_all">
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
                  className={`form-group add-comment-div ${
                    this.shouldFormBeOpaque() ? `make-opaque` : null
                  }`}
                >
                  {this.state.showComments ? (
                    <div className="form-group mt20">
                      <Field
                        name="comment"
                        placeholder="Comment Description"
                        component={AutoResizeTextarea}
                        className="form-control"
                      />
                    </div>
                  ) : (
                    <a
                      onClick={() => {
                        this.analytics.comment = true;
                        this.setState({ showComments: true });
                      }}
                      className="comment-link-optional"
                    >
                      + Add Comments(Optional)
                    </a>
                  )}
                </div>
                <div
                  className="Modal__actions"
                  onMouseEnter={this.handleHoverIn}
                  onMouseLeave={this.handleHoverOut}
                >
                  <button
                    className="btn btn-primary btn-block"
                    disabled={
                      this.isRefundButtonDisabled() ||
                      this.shouldDisableRefundIfUnchecked() ||
                      this.state.refundApiInProgress
                    }
                  >
                    Issue <RefundType partial={partial} isTitleCase={true} /> refund
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </Query>
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
      refundOfflinePayment,
      updateRefundStatusInNotes,
      voidPayment,
      fetchPayment,
      fetchRefunds,
      fetchTransfers,
      fetchEzetapKeys,
      ...NotificationsActions,
    },
    dispatch,
  );

export default compose(
  reduxForm({
    form: 'refundModal',
  }),
  connect(mapStateToProps, mapDispatchToProps),
)(withI18Service(RefundModal));
