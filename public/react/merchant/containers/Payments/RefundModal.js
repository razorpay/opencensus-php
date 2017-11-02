import AsyncButton from 'react-async-button';
import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import * as NotificationsActions from 'rzp/modules/notifications';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
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

function amountValidation(value, allValues, props) {
  debugger;

  value = value || '';

  if (!value) {
    return 'Amount is required';
  }

  if (isNaN(value) || (value.split('.')[1] || []).length > 2) {
    return 'Amount can only be a Number with atmost 2 decimal places.';
  }
  if (value < 0) {
    return `Amount can't be negative.`;
  }
  if (value > (props.payment.amount - props.payment.amount_refunded) / 100) {
    return `Amount can't be greater than the amount paid (${(props.payment
      .amount -
      props.payment.amount_refunded) /
      100}).`;
  }
}

const RefundType = ({ partial, isTitleCase = false }) => {
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

  save = props => {
    // For partial refund, if reverse all is checked, we cannot reverse when there is more than 1 transfer on the payment.
    if (
      props.partial &&
      props.reverse_all &&
      this.props.transfers.items.length > 1
    ) {
      var errorMsg =
        'Reversals cannot be automated when partially refunding a payment that has more than 1 transfer.' +
        ' Create reversals manually before attempting the refund.';

      this.props.showNotification({
        type: 'error',
        message: errorMsg,
        closeTimeout: 10000,
      });

      return;
    }

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
          let payment = this.props.payment;
          let data = {
            amount: rupeesToPaise(props.amount),
            comment: props.comment,
            reverse_all: props.reverse_all ? '1' : '0',
          };

          if (!props.partial) {
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
      .catch(() => {});
  };

  render() {
    const { handleSubmit, payment, transfers } = this.props;

    const refundableAmount = payment.amount - payment.amount_refunded,
      amountEntered = rupeesToPaise(this.props.payable_amount),
      partial = amountEntered < refundableAmount;

    return (
      <div>
        <ModalHeader
          title="Refund Payment"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Refund Amount</label>
              <div class="input-group">
                <div class="input-group-addon">INR</div>
                <Field
                  name="amount"
                  component={InputField}
                  class="form-control"
                  type="number"
                  validate={amountValidation}
                  placeholder="Enter the refund amount"
                />
              </div>
              <small class="help-block">
                This will be a{' '}
                <b>
                  <RefundType partial={partial} /> refund
                </b>.
                {!partial && <span>Change amount for a partial refund.</span>}
              </small>
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
                  <label for="reverse_all">
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
