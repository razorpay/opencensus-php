import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Amount from 'rzp/ui/Amount';
import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
} from 'rzp/utils/rzp-utils';
import {
  fetchTransfer,
  fetchReversals,
  reverseTransfer,
} from 'merchant/modules/marketplace/transfer';

import { closeModal } from 'rzp/modules/modals';

// returns value in paise
const getReversibleAmount = transfer => {
  return transfer.amount - transfer.amount_reversed;
};

const isPartialTransfer = props => {
  const amountEntered = rupeesToPaise(props.amountEntered),
    reversibleAmount = getReversibleAmount(props.transfer);

  return amountEntered < reversibleAmount;
};

const amountValidation = props => {
  const value = props.amountEntered || '';

  if (!value) {
    return 'Amount is required';
  }

  if (isNaN(value) || (value.toString().split('.')[1] || []).length > 2) {
    return 'Amount can only be a Number with atmost 2 decimal places.';
  }

  if (value < 0) {
    return `Amount can't be negative.`;
  }

  const reversableAmount = getReversibleAmount(props.transfer);

  if (rupeesToPaise(value) > reversableAmount) {
    return (
      `Amount can't be greater than the total Reversible` +
      ` Amount (${paiseToRupees(reversableAmount)}).`
    );
  }
};

const ReversalType = props => {
  const { isTitleCase } = props;

  let reversalType = isPartialTransfer(props) ? 'partial' : 'full';

  if (isTitleCase) {
    reversalType = titleCase(reversalType);
  }

  return <span>{reversalType}</span>;
};

const selector = formValueSelector('reversalModal');
@connect(
  state => {
    let partial = selector(state, 'partial');
    let amountEntered = selector(state, 'amount');

    return {
      ...state.session,
      ...state.transfer,
      user: state.session.user,
      partial,
      amountEntered,
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
          if (isPartialTransfer(this.props)) {
            data = {
              amount: rupeesToPaise(props.amount),
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

    const amountError = amountValidation(this.props),
      isPartial = isPartialTransfer(this.props);

    return (
      <div>
        <ModalHeader
          title="Reverse Transfer"
          onCloseClick={this.props.closeModal}
        />

        <div className="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div className="form-group">
              <label className="label-required">Reversal Amount</label>
              <div className="input-group">
                <div className="input-group-addon">INR</div>
                <Field
                  name="amount"
                  component={InputField}
                  type="number"
                  autoComplete="off"
                  className="form-control"
                  placeholder="Enter the reversal amount"
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
                    <ReversalType {...this.props} /> reversal
                  </b>.
                  {!isPartial && (
                    <span> Change amount for a partial reversal.</span>
                  )}
                </small>
              )}
            </div>
            <div className="form-group">
              <label>Comments (Optional)</label>
              <Field
                name="comment"
                component={InputField}
                class="form-control"
                placeholder="Add an optional comment"
              />
            </div>
            <div class="Modal__actions">
              <button class="btn btn-primary btn-block">
                Create <ReversalType {...this.props} isTitleCase={true} />{' '}
                Reversal
              </button>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
