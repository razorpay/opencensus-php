import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { Link } from 'react-router-dom';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import * as NotificationsActions from 'rzp/modules/notifications';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import { AmountTooltip } from 'rzp/ui/Amount';
import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
} from 'rzp/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/modules/payments/details';
import { closeModal } from 'rzp/modules/modals';
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

  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
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

  save = props => {
    const partial = isPartialPayment(this.props),
      hasAmountErrors = amountValidation(this.props);

    if (hasAmountErrors) {
      return;
    }
    this.props.fetchRefundFee(this.props.payment, props.amount);

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

    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Click - Issue Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
      speed_requested: props.instant_refund ? 'optimum' : 'normal',
    });

    // If instant_refund is checked
    if (props.instant_refund) {
      this.context
        .confirm({
          header: 'Do you want to refund this payment?',
          message: () => (
            <div>
              <div class="text-semi-muted">
                <p>
                  This payment will be instantly refunded to the customer. A fee
                  of &#8377; {this.props.refundFee.data.fee} will be charged
                  from your unsettled balance.
                </p>
              </div>
              <div class="confirm-note">
                <p>Note</p>
                <p>
                  If the instant refund is unsuccessful, the fee will be
                  reversed. The payment will still be reversed in 5-7 days.
                </p>
              </div>
            </div>
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
    } else {
      this.context
        .confirm({
          header: 'Are you sure you want to refund this payment?',
          message: props.reverse_all
            ? 'Reversals will be automatically created for all transfers on this payment, before the refund'
            : null,
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

            let payment = this.props.payment;
            let data = {
              amount: rupeesToPaise(props.amount),
              comment: props.comment,
              reverse_all: props.reverse_all ? '1' : '0',
            };

            if (!partial) {
              data.amount = payment.amount - payment.amount_refunded;
            }

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
    const { payment } = this.props;
    const { data } = this.props.current_balance;

    let amount = payment.amount;
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

  getInstantRefundClassNames = boolVal => {
    if (boolVal) {
      return 'checkbox instant-refund-disable';
    } else if (
      this.props.current_balance.loading === true ||
      boolVal === false
    ) {
      return 'checkbox';
    }
  };

  showInstantRefund = (payment, isInstantDisabled) => {
    if (
      showWhenUtil({ featureEnabled: 'card_transfer_refund' }) &&
      payment.instant_refund_support &&
      payment.instant_refund_support === true
    ) {
      return (
        <div>
          <div class={this.getInstantRefundClassNames(isInstantDisabled)}>
            <label>
              <Field
                name="instant_refund"
                component="input"
                type="checkbox"
                disabled={isInstantDisabled}
                onChange={this.onInstantRefundCheckboxClick}
              />
              <b>Refund Instantly</b>
            </label>
            <span
              data-tooltip="You can refund this payment instantly for a small fee"
              data-tooltip-position="top"
              onMouseEnter={this.onInstantRefundTooltipHover}
            >
              <i class="i i-help" />
            </span>
          </div>
          {this.props.current_balance.loading ? (
            <div>Loading...</div>
          ) : isInstantDisabled ? (
            <div class="low-funds">
              Your account does not have sufficient balance to instantly refund
              this payment.
              <Link to={'/addfunds'} target="_blank">
                Add Funds
                <i class="i i-external-link" />
              </Link>
            </div>
          ) : null}
        </div>
      );
    } else return null;
  };

  onInstantRefundCheckboxClick = e => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: e.target.value
        ? 'Unchecked - Instant Refund'
        : 'Checked - Instant Refund',
      eventLabel: `payment_id=${this.props.payment.id}`,
    });
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
                  {!partial && <span>Change amount for a partial refund.</span>}
                </small>
              )}
            </div>
            {transfers.items.length > 0 && (
              <div class="form-group">
                <div class="checkbox rzpCheckbox">
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
            <div class="form-group">
              <label>Comments (Optional)</label>
              <Field
                name="comment"
                component={AutoResizeTextarea}
                class="form-control"
              />
            </div>
            {this.showInstantRefund(payment, isInstantDisabled)}
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
