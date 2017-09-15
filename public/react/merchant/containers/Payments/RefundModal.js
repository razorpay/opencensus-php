import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import { isBlank } from 'rzp/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/modules/payments/details';
import { closeModal } from 'rzp/modules/modals';

const amountValidation = (value, allValues, props) => {
  value = value || '';
  if (allValues.partial) {
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

    if (this.props.user.tags.indexOf('Marketplace') !== -1) {
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
            amount: props.amount * 100,
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

    return (
      <div>
        <ModalHeader
          title="Refund Payment"
          onCloseClick={this.props.closeModal}
        />

        <form
          class="form-horizontal payment-link-form"
          onSubmit={handleSubmit(this.save)}
        >
          <div class="modal-body">
            <div class="form-group">
              <label class="col-sm-4 control-label">
                <div>Partial Refund</div>
              </label>
              <div class="col-sm-8">
                <div class="checkbox">
                  <label class="i-checks">
                    <Field
                      name="partial"
                      id="partial"
                      component="input"
                      type="checkbox"
                      class="form-control"
                    />
                    <i />
                  </label>
                </div>
              </div>
            </div>
            {this.props.partial
              ? <div class="form-group">
                  <label class="col-sm-4 control-label">
                    <div>Amount</div>
                    <small>(in INR)</small>
                  </label>
                  <div class="col-sm-8">
                    <Field
                      name="amount"
                      component={InputField}
                      class="form-control"
                      validate={amountValidation}
                      placeholder="Enter the refund amount"
                    />
                    <i />
                  </div>
                </div>
              : null}
            {transfers.items.length > 0
              ? <div class="form-group">
                  <label class="col-sm-4 control-label">
                    <div>
                      Reverse All{' '}
                      <a href="https://razorpay.com/docs/route/operations/#reversals">
                        Route Transfers
                      </a>
                    </div>
                  </label>
                  <div class="col-sm-8">
                    <div class="checkbox">
                      <label class="i-checks">
                        <Field
                          name="reverse_all"
                          id="reverse_all"
                          component="input"
                          type="checkbox"
                          class="form-control"
                        />
                        <i />
                      </label>
                    </div>
                  </div>
                </div>
              : null}

            <div class="form-group">
              <label class="col-sm-4 control-label">
                <div>Comments</div>
              </label>
              <div class="col-sm-8">
                <Field
                  name="comment"
                  component={InputField}
                  class="form-control"
                  placeholder="Add an optional comment"
                />
                <i />
              </div>
            </div>

            <div class="form-group">
              <div class="col-sm-8 col-sm-offset-4">
                The payment will be{' '}
                {this.props.partial &&
                (payment.amount - payment.amount_refunded) / 100 !==
                  Number(this.props.payable_amount)
                  ? 'partially '
                  : 'completely '}
                refunded with the refund amount set to{' '}
                <b>
                  {(this.props.partial
                    ? this.props.payable_amount
                    : (payment.amount - payment.amount_refunded) / 100) ||
                    0}{' '}
                  INR
                </b>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="Refund"
              pendingText="Refunding..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
