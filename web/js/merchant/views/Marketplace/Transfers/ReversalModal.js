import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import InputField from 'common/ui/Forms/InputField';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';

import { rupeesToPaise, paiseToRupees, titleCase } from 'common/utils/rzp-utils';
import {
  fetchTransfer,
  fetchReversals,
  reverseTransfer,
} from 'merchant/reducers/marketplace/transfers/details';

import { closeModal } from 'merchant_common/reducers/modals';

// returns value in paise
const getReversibleAmount = (transfer) => {
  return transfer.amount - transfer.amount_reversed;
};

const isPartialTransfer = (props) => {
  const amountEntered = rupeesToPaise(props.amountEntered);
  const reversibleAmount = getReversibleAmount(props.transfer);

  return amountEntered < reversibleAmount;
};

const amountValidation = (props) => {
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

  return '';
};

const ReversalType = (props) => {
  const { isTitleCase } = props;

  let reversalType = isPartialTransfer(props) ? 'partial' : 'full';

  if (isTitleCase) {
    reversalType = titleCase(reversalType);
  }

  return <span>{reversalType}</span>;
};

const selector = formValueSelector('reversalModal');
@connect(
  (state) => {
    const partial = selector(state, 'partial');
    const amountEntered = selector(state, 'amount');
    const notesEntered = selector(state, 'notes');

    return {
      ...state.session,
      ...state.transfer,
      user: state.session.user,
      partial,
      amountEntered,
      notesEntered,
    };
  },
  {
    closeModal,
    reverseTransfer,
    fetchTransfer,
    fetchReversals,
    ...NotificationsActions,
  },
)
@reduxForm({
  form: 'reversalModal',
  initialValues: {
    notes: [{}],
  },
})
export default class ReversalModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  UNSAFE_componentWillMount() {
    const transfer = this.props.transfer;

    this.props.initialize({
      partial: false,
      amount: `${(transfer.amount - transfer.amount_reversed) / 100}`,
      notes: [{}],
    });
  }

  save = (props) => {
    this.context
      .confirm({
        header: 'Are you sure you want to reverse this transfer?',
        message: null,
        affirmativeLabel: 'Yes, Reverse',
        affirmativePendingLabel: 'Reversing...',
        abortLabel: "No, don't!",
        action: () => {
          const transfer = this.props.transfer;

          let data = {};
          if (isPartialTransfer(this.props)) {
            data = {
              amount: rupeesToPaise(props.amount),
            };
          }

          let transformedNotes = props.notes;
          const linked_account_notes = [];

          if (transformedNotes && transformedNotes.length > 0) {
            transformedNotes = transformedNotes.reduce((result, current) => {
              result[current.key] = current.value;
              if (current.also_linked_account) {
                linked_account_notes.push(current.key);
              }
              return result;
            }, {});
          }

          if (transformedNotes) {
            data = {
              ...(data || {}),
              notes: transformedNotes,
              linked_account_notes,
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
              if (errors) {
                this.props.showNotification({
                  type: 'error',
                  message: errors,
                  closeTimeout: 5000,
                });
              }
            });
        },
      })
      .catch(() => {});
  };

  render() {
    const { handleSubmit, transfer } = this.props;

    const amountError = amountValidation(this.props);
    const isPartial = isPartialTransfer(this.props);

    return (
      <div>
        <ModalHeader title="Reverse Transfer" onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Reversal Amount</label>
              <div class="input-group">
                <div class="input-group-addon">{transfer.currency}</div>
                <Field
                  name="amount"
                  component={InputField}
                  type="number"
                  autoComplete="off"
                  class="form-control"
                  placeholder="Enter the reversal amount"
                />
              </div>
              {!!amountError ? (
                <div class="InputField__ErrorText text-danger">{amountError}</div>
              ) : (
                <small class="help-block">
                  This will be a{' '}
                  <b>
                    <ReversalType {...this.props} /> reversal
                  </b>
                  .{!isPartial && <span> Change amount for a partial reversal.</span>}
                </small>
              )}
            </div>
            <div class="form-group">
              <label>Internal Notes</label>
              <FieldArray
                name="notes"
                component={NotesFieldArray}
                showLinkedAccountOpt={true}
                customAddMsg="+ Add New"
              />
            </div>
            <div class="Modal__actions">
              <button class="btn btn-primary btn-block">
                Create <ReversalType {...this.props} isTitleCase={true} /> Reversal
              </button>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
