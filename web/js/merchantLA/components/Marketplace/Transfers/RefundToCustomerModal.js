import React from 'react';
import { connect } from 'react-redux';

import Input from 'component/Input';
import Button from 'component/Button';
import Form from 'component/Form';
import { openModal, closeModal } from 'rzp/modules/modals';
import ModalHeader from 'rzp/ui/ModalHeader';
import * as NotificationsActions from 'rzp/modules/notifications';
import { reverseTransfer } from 'merchantLA/modules/marketplace/transfer';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
} from 'rzp/utils/rzp-utils';

const isPartialPayment = props => {
  const refundableAmount = props.payment.amount - props.payment.amount_reversed,
    amountEntered = rupeesToPaise(props.payable_amount);

  return amountEntered < refundableAmount;
};

const amountValidation = props => {
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

  const refundableAmount = props.payment.amount - props.payment.amount_reversed;

  if (rupeesToPaise(value) > refundableAmount) {
    return (
      `Amount can't be greater than the total Refundable` +
      ` Amount (${paiseToRupees(refundableAmount)}).`
    );
  }
};

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
    let payable_amount = selector(state, 'amount');

    return {
      payment: state.transfer.entity.__stashed__,
      partial,
      payable_amount,
    };
  },
  { closeModal, openModal, reverseTransfer, ...NotificationsActions }
)
@reduxForm({
  form: 'refundModal',
})
export default class RefundToCustomerModal extends React.Component {
  constructor() {
    super(...arguments);
    this.state = {
      isLoading: false,
    };
  }

  componentWillMount() {
    const payment = this.props.payment;

    this.props.initialize({
      partial: false,
      amount: (payment.amount - payment.amount_reversed) / 100 + '',
    });
  }

  save = props => {
    const hasAmountErrors = amountValidation(this.props);

    if (hasAmountErrors) {
      return;
    }

    const { payment, transfer: { id }, reverseTransfer } = this.props,
      partial = isPartialPayment(this.props),
      data = {
        amount: rupeesToPaise(props.amount),
      };

    if (!partial) {
      data.amount = payment.amount - payment.amount_reversed;
    }

    this.setState({
      isLoading: true,
    });

    return reverseTransfer(id, {
      ...data,
      customer_refund: true,
    })
      .then(_ => {
        this.props.showNotification({
          type: 'success',
          message: 'Payment refunded',
          closeTimeout: 5000,
        });

        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
          closeTimeout: 5000,
        });

        this.setState({
          isLoading: false,
        });
      });
  };

  render() {
    const { handleSubmit, payment } = this.props,
      { isLoading } = this.state,
      amountError = amountValidation(this.props),
      partial = isPartialPayment(this.props);

    return (
      <div className="refund-to-customer-modal">
        <ModalHeader
          title="Refund to Customer"
          onCloseClick={this.props.closeModal}
        />
        <div className="modal-body">
          <form class="entity-container" onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Reversal Amount</label>
              <div class="input-group">
                <div class="input-group-addon">{payment.currency}</div>
                <Field
                  name="amount"
                  component={InputField}
                  class="form-control"
                  type="number"
                  placeholder="Enter the refund amount"
                />
              </div>
              {!!amountError ? (
                <div class="text-danger">{amountError}</div>
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
            <Button.Primary class="form-control">
              {isLoading ? (
                <span class="btn-pending">
                  <span class="spin-btn white" />
                </span>
              ) : (
                <React.Fragment>
                  <RefundType partial={partial} isTitleCase={true} /> refund
                </React.Fragment>
              )}
            </Button.Primary>
          </form>
        </div>
      </div>
    );
  }
}
