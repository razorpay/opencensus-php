import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Amount from 'rzp/ui/Amount';
import { isBlank } from 'rzp/utils/rzp-utils';
import {
  fetchTransfer,
  fetchReversals,
  reverseTransfer,
} from 'merchant/modules/marketplace/transfer';

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
    if (
      value >
      (props.transfer.amount - props.transfer.amount_reversed) / 100
    ) {
      return `Amount can't be greater than the transfer amount(${(props.transfer
        .amount -
        props.transfer.amount_reversed) /
        100}).`;
    }
  }
};

const selector = formValueSelector('reversalModal');
@connect(
  state => {
    let partial = selector(state, 'partial');
    let reversable_amount = selector(state, 'amount');

    return {
      ...state.session,
      ...state.transfer,
      user: state.session.user,
      partial,
      reversable_amount,
    };
  },
  {
    closeModal,
    reverseTransfer,
    fetchTransfer,
    fetchReversals,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'reversalModal',
})
export default class ReversalModal extends Component {
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
    let transfer = this.props.transfer;

    this.props.initialize({
      partial: false,
      amount: (transfer.amount - transfer.amount_reversed) / 100 + '',
    });
  }

  save = props => {
    this.context
      .confirm({
        header: 'Are you sure you want to reverse this transfer?',
        message: null,
        affirmativeLabel: 'Yes, Reverse',
        affirmativePendingLabel: 'Reversing...',
        abortLabel: "No, don't!",
        action: () => {
          let transfer = this.props.transfer;

          let data = null;
          if (props.partial) {
            data = {
              amount: props.amount * 100,
            };
          }

          if (props.comment) {
            data = {
              ...(data || {}),
              notes: { comment: props.comment },
            };
          }

          return this.props
            .reverseTransfer(transfer.id, data)
            .then(() => {
              this.props.showNotification({
                type: 'success',
                message: 'Transfer reversed',
                closeTimeout: 5000,
              });
              this.props.fetchTransfer(transfer.id);
              this.props.fetchReversals(transfer.id);
              this.props.closeModal();

              if (typeof this.props.onReverse === 'function') {
                this.props.onReverse();
              }
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
    const { handleSubmit, transfer } = this.props;

    return (
      <div>
        <ModalHeader
          title="Reverse Transfer"
          onCloseClick={this.props.closeModal}
        />

        <form
          class="form-horizontal payment-link-form"
          onSubmit={handleSubmit(this.save)}
        >
          <div class="modal-body">
            <div class="form-group">
              <label class="col-sm-4 control-label">
                <div>Partial Reversal</div>
              </label>
              <div class="col-sm-8">
                <div class="checkbox rzpCheckbox">
                  <Field
                    name="partial"
                    id="partial"
                    component="input"
                    type="checkbox"
                    class="form-control"
                  />
                  <label for="partial" />
                </div>
              </div>
            </div>
            {this.props.partial ? (
              <div class="form-group">
                <label class="col-sm-4 control-label">
                  <div>Amount</div>
                  <small>(in INR)</small>
                </label>
                <div class="col-sm-8">
                  <Field
                    name="amount"
                    component={InputField}
                    autoComplete="off"
                    class="form-control"
                    validate={amountValidation}
                    placeholder="Enter the reversal amount"
                  />
                  <i />
                </div>
              </div>
            ) : null}

            <div class="form-group">
              <div class="col-sm-8 col-sm-offset-4">
                The transfer amount will be{' '}
                <b>
                  {this.props.partial &&
                  (transfer.amount - transfer.amount_reversed) / 100 !==
                    Number(this.props.reversable_amount)
                    ? 'partially '
                    : 'completely '}
                  reversed{' '}
                </b>if the reversal amount set to{' '}
                <b>
                  {(this.props.partial
                    ? this.props.reversable_amount
                    : (transfer.amount - transfer.amount_reversed) / 100) ||
                    0}{' '}
                  INR
                </b>
              </div>
            </div>

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
              text="Reverse"
              pendingText="Reversing..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
