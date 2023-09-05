import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fetchCreditBalance } from 'merchant/reducers/credits';
import {
  reverseTransfer,
  fetchTransfer,
  fetchReversals,
} from 'merchantLA/reducers/marketplace/transfer';
import { rupeesToPaise, paiseToRupees } from 'common/utils/rzp-utils';
import {
  amountValidation,
  isPartialPayment,
  RefundType,
} from 'merchant/views/Transactions/v1/Payments/components/RefundModal';

import { trackClickCreateRefund } from './ga';

@connect(
  (state) => {
    return {
      payment: {
        ...state.transfer.entity,
        amount_reversed: state.transfer.entity.amount_reversed || 0,
        amount_refunded: state.transfer.entity.amount_reversed || 0,
      },
    };
  },
  {
    closeModal,
    openModal,
    reverseTransfer,
    fetchReversals,
    fetchTransfer,
    fetchCreditBalance,
    ...NotificationsActions,
  },
)
export default class RefundToCustomerModal extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      partial: false,
      payable_amount: paiseToRupees(props.payment.amount - props.payment.amount_reversed),
      notes: [{}],
    };
  }

  save = (e) => {
    e.preventDefault();

    this.context
      .confirm({
        header: 'Are you sure you want to refund?',
        message: null,
        affirmativeLabel: 'Yes, Refund',
        affirmativePendingLabel: 'Refunding...',
        abortLabel: "No, don't!",
        action: () => {
          const hasAmountErrors = amountValidation({
            ...this.state,
            ...this.props,
          });

          if (hasAmountErrors) {
            return;
          }

          const { payment, reverseTransfer } = this.props;
          const partial = isPartialPayment({ ...this.state, ...this.props });
          const id = payment.id;
          const linked_account_notes = [];
          let transformedNotes = this.state.notes;
          let data = {
            amount: rupeesToPaise(this.state.payable_amount),
          };

          if (!partial) {
            data.amount = payment.amount - payment.amount_reversed;
          }

          if (transformedNotes && transformedNotes.length > 0) {
            transformedNotes = transformedNotes.reduce((result, current) => {
              result[current.key] = current.value;
              if (current.also_linked_account) {
                // TODO : not sure why this is happening linked_account_notes is not there in the code itself.
                linked_account_notes.push(current.key);
              }
              return result;
            }, {});
          }

          if (transformedNotes) {
            data = {
              ...(data || {}),
              notes: transformedNotes, // Change it to linked_account_notes
            };
          }

          this.setState({
            isLoading: true,
          });

          // eslint-disable-next-line consistent-return
          return reverseTransfer(id, {
            ...data,
            customer_refund: true,
          })
            .then((_) => {
              this.props.showNotification({
                type: 'success',
                message: 'Payment refunded',
                closeTimeout: 5000,
              });

              Promise.all([this.props.fetchTransfer(id), this.props.fetchReversals(id)]);

              trackClickCreateRefund(`${partial ? 'partial' : 'full'} | Yes `);

              this.props.fetchCreditBalance();

              this.props.closeModal();
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
                closeTimeout: 5000,
              });

              this.setState({
                isLoading: false,
              });
            });
        },
      })
      .catch(() => {
        trackClickCreateRefund(
          `${isPartialPayment({ ...this.state, ...this.props }) ? 'partial' : 'full'} | No `,
        );
      });
  };

  handleAmout = (e) => {
    this.setState({
      payable_amount: Number(e.target.value),
    });
  };

  handleNotesChange = (notes) => {
    this.setState({ notes });
  };

  render() {
    const { payment } = this.props;
    const { isLoading, payable_amount } = this.state;
    const amountError = amountValidation({ ...this.state, ...this.props });
    const partial = isPartialPayment({ ...this.state, ...this.props });

    return (
      <div className="refund-to-customer-modal">
        <ModalHeader title="Refund to Customer" onCloseClick={this.props.closeModal} />
        <div className="modal-body">
          <form class="entity-container" onSubmit={this.save}>
            <div class="form-group">
              <label class="label-required">Amount</label>
              <div class="input-group">
                <div class="input-group-addon">{payment.currency}</div>
                <div class="InputField">
                  <input
                    name="amount"
                    type="number"
                    class="form-control"
                    value={payable_amount}
                    onChange={this.handleAmout}
                    placeholder="Enter the refund amount"
                  />
                </div>
              </div>
              {!!amountError ? (
                <div class="InputField__ErrorText text-danger">{amountError}</div>
              ) : (
                <small class="help-block">
                  This will be reflected as a{' '}
                  <b>
                    <RefundType partial={partial} /> reversal
                  </b>
                  .{!partial && <span>Change amount for a partial refund.</span>}
                </small>
              )}
            </div>
            <Input.PairList
              name="notes"
              label="Internal Notes"
              class="Input--vTop"
              defaultValue={[{}]}
              onChange={this.handleNotesChange}
            />
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
