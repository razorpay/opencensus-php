import AsyncButton from 'react-async-button';
import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import moment from 'moment';

import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import { SingleDatePicker } from 'react-dates';
import { nextWorkingDay, isHoliday } from 'common/utils/bankHolidays';
import { titleCase } from 'common/utils/rzp-utils';
import TransferReversal from 'merchantLA/components/Marketplace/Transfers/TransferReversal';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

let initialState = {
  onHold: 'false',
  holdUntil: null,
  date: null,
  focused: false,
  dateError: null,
  errors: null,
  editView: false,
};

const SettlementText = ({ data, transfer }) => {
  if (transfer.recipient_settlement && transfer.recipient_settlement.status) {
    return <span>{titleCase(transfer.recipient_settlement.status)}</span>;
  }

  return (
    <div>
      <div>
        {data.onHold === 'false' ? (
          <span className="text-success">Scheduled</span>
        ) : data.holdUntil ? (
          <span className="text-warning transfer-scheduled-text">
            Scheduled for&nbsp;
            <Time value={data.date.toDate() / 1000} format="Do MMM YYYY" />
          </span>
        ) : (
          <span className="text-danger">On Hold</span>
        )}
        <span>&nbsp;&nbsp;</span>
      </div>
      {data.onHold === 'false' && (
        <div className="text-fade">
          Transfers scheduled to settle on bank holidays will get settled on the
          next working day.
        </div>
      )}
    </div>
  );
};

@connect(state => ({
  isShowParentPaymentIdEnabled: state.session.user.isShowParentPaymentIdEnabled,
}))
export default class TransferDetails extends Component {
  state = { ...initialState };

  UNSAFE_componentWillReceiveProps(nextProps) {
    const transfer = nextProps.transfer;

    initialState = {
      ...initialState,
      onHold: (transfer.on_hold
        ? transfer.on_hold_until ? 'on_hold_until' : 'on_hold'
        : false
      ).toString(),
      holdUntil: transfer.on_hold_until,
      date: transfer.on_hold_until
        ? moment((transfer.on_hold_until + 600) * 1000)
        : null,
    };

    this.setState(initialState);
  }

  render() {
    const {
      transfer,
      isLoading,
      statusMsg,
      reversals,
      onClose,
      parentAccountName,
      showRefundToCustomer,
      isShowParentPaymentIdEnabled,
    } = this.props;

    const nextWorkingDate = nextWorkingDay(
      moment()
        .startOf('day')
        .toDate(),
      3
    );

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {onClose && (
                <button
                  type="button"
                  class="close close-secondary"
                  onClick={onClose}
                >
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Transfer ID: <strong>{transfer.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <EntityDetailRow label="Parent Account">
                  <Definition>
                    <b>{parentAccountName}</b>
                  </Definition>
                </EntityDetailRow>

                {isShowParentPaymentIdEnabled && (
                  <EntityDetailRow label="Parent Payment Id">
                    {transfer.parent_payment_id}
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Amount">
                  <Amount
                    value={transfer.amount}
                    currency={transfer.currency}
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Reversal">
                  <TransferReversal
                    transfer={transfer}
                    reversals={reversals}
                    showRefundToCustomer={showRefundToCustomer}
                  />
                </EntityDetailRow>

                <EntityDetailRow
                  label="Amount"
                  value={_ => (
                    <Time
                      value={transfer.created_at}
                      format="Do MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <EntityDetailRow label="Settlement">
                  <SettlementText data={this.state} transfer={transfer} />
                </EntityDetailRow>

                {/* Notes */}
                <EntityDetailRow label="Notes">
                  {transfer.notes &&
                    (Object.keys(transfer.notes).length === 0
                      ? '--'
                      : Object.keys(transfer.notes).map((key, index) => (
                          <div className="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(transfer.notes[key])}
                            </Definition>
                          </div>
                        )))}
                </EntityDetailRow>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
