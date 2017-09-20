import AsyncButton from 'react-async-button';
import { Component } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';

import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import { SingleDatePicker } from 'react-dates';
import { nextWorkingDay, isHoliday } from 'rzp/utils/bankHolidays';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Fee from 'merchant/components/Fee';
import TransferReversal from 'merchant/components/Marketplace/Transfers/TransferReversal';

let initialState = {
  onHold: 'false',
  holdUntil: null,
  date: null,
  focused: false,
  dateError: null,
  errors: null,
  editView: false,
};

export default class TransferDetails extends Component {
  constructor(props) {
    super(props);

    this.state = { ...initialState };

    // Do not want to use arrow member functions as new
    // function will be created for every instance
    // #antipattern
    this.onDateChange = this.onDateChange.bind(this);
    this.onScheduleChange = this.onScheduleChange.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.onDismiss = this.onDismiss.bind(this);
    this.onEdit = this.onEdit.bind(this);
  }

  componentWillReceiveProps(nextProps) {
    const transfer = nextProps.transfer;

    initialState = {
      ...initialState,
      onHold: (transfer.on_hold
        ? transfer.on_hold_until ? 'on_hold_until' : 'on_hold'
        : false).toString(),
      holdUntil: transfer.on_hold_until,
      date: transfer.on_hold_until
        ? moment((transfer.on_hold_until + 600) * 1000)
        : null,
    };

    this.setState(initialState);
  }

  onDateChange(date) {
    this.setState({
      date,
      holdUntil: ((date.toDate() - 60000) / 1000) | 0,
    });
  }

  onEdit(e) {
    e.preventDefault();
    this.setState({ editView: true });
  }

  onScheduleChange(e) {
    const value = e.target.value;

    this.setState({
      onHold: value,
      ...(value !== 'on_hold_until' && {
        date: null,
        holdUntil: null,
        dateError: null,
      }),
    });
  }

  onSubmit(e) {
    e.preventDefault();

    if (this.state.onHold === 'on_hold_until' && !this.state.holdUntil) {
      return this.setState({ dateError: true });
    }

    if (typeof this.props.onTransferUpdate !== 'function') {
      return;
    }

    let data = {};

    if (this.state.onHold !== 'false') {
      data.on_hold = 1;

      if (this.state.onHold === 'on_hold_until') {
        data.on_hold_until = this.state.holdUntil;
      }
    } else {
      data.on_hold = 0;
    }

    return this.props
      .onTransferUpdate(data)
      .then(() => {
        initialState = {
          ...initialState,
          ...this.state,
          editView: false,
        };
        this.setState(initialState);
      })
      .catch(({ errors }) => {
        this.setState({ errors });
      });
  }

  onDismiss(e) {
    e.preventDefault();
    this.setState({ ...initialState });
  }

  render() {
    const {
      transfer,
      isLoading,
      statusMsg,
      openReversalModal,
      reversals,
      onClose,
    } = this.props;

    const nextWorkingDate = nextWorkingDay(moment().startOf('day').toDate(), 3);

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : <div class="panel panel-default SliderPanel">
              <div class="panel-heading">
                {onClose &&
                  <button
                    type="button"
                    class="close close-secondary"
                    onClick={onClose}
                  >
                    <i class="icon icon-arrow-back" />
                    <i class="icon icon-close" />
                  </button>}
                Transfer ID: <strong>{transfer.id}</strong>
              </div>

              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <EntityDetailRow label="Linked Account">
                    <Definition>
                      <span>
                        {transfer.recipient_details.name}
                      </span>
                      {transfer.recipient_details.email &&
                        <span>
                          {transfer.recipient_details.email}
                        </span>}
                      <code>
                        {transfer.recipient}
                      </code>
                    </Definition>
                  </EntityDetailRow>

                  <EntityDetailRow label="Amount">
                    <ContentToggler>
                      <Amount value={transfer.amount} />
                      <div className="m-t">
                        <Fee
                          totalFee={transfer.fees}
                          rzpFee={transfer.fees - transfer.tax}
                          tax={transfer.tax}
                        />
                      </div>
                    </ContentToggler>
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Created At"
                    value={() =>
                      <Time
                        value={transfer.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />}
                  />

                  <EntityDetailRow label="Settlement">
                    {this.state.editView
                      ? <form
                          onSubmit={this.onSubmit}
                          name="updatePaymentTransfer"
                        >
                          <div class="RadioButton">
                            <label>
                              <input
                                type="radio"
                                name="onHold"
                                value="on_hold_until"
                                checked={this.state.onHold === 'on_hold_until'}
                                onChange={this.onScheduleChange}
                              />
                              <div>
                                <div class="RadioButton__button" />
                                <div class="RadioButton__label">
                                  <div>
                                    <span>Schedule settlement on</span>
                                  </div>
                                </div>
                              </div>
                            </label>
                          </div>
                          <div className="transfers-onhold-datepicker">
                            <SingleDatePicker
                              id="holdUntil"
                              name="holdUntil"
                              numberOfMonths={1}
                              disabled={this.state.onHold !== 'on_hold_until'}
                              isDayBlocked={date => {
                                date = date.clone().startOf('day').toDate();

                                return (
                                  date < nextWorkingDate || isHoliday(date)
                                );
                              }}
                              date={this.state.date}
                              onDateChange={this.onDateChange}
                              focused={this.state.focused}
                              onFocusChange={({ focused }) =>
                                this.setState({ focused })}
                            />
                            {this.state.dateError &&
                              <div className="text-small text-danger text-right">
                                Please select a schedule date
                              </div>}
                          </div>
                          <div class="RadioButton">
                            <label>
                              <input
                                type="radio"
                                name="onHold"
                                value="on_hold"
                                checked={this.state.onHold === 'on_hold'}
                                onChange={this.onScheduleChange}
                              />
                              <div>
                                <div class="RadioButton__button" />
                                <div class="RadioButton__label">
                                  <span>Put on hold</span>
                                  <div class="text-fade">
                                    The settlement will be on hold till
                                    specified otherwise.
                                  </div>
                                </div>
                              </div>
                            </label>
                          </div>
                          <div class="RadioButton">
                            <label>
                              <input
                                type="radio"
                                name="onHold"
                                value={'false'}
                                checked={this.state.onHold === 'false'}
                                onChange={this.onScheduleChange}
                              />
                              <div>
                                <div class="RadioButton__button" />
                                <div class="RadioButton__label">
                                  <span>Settle Now</span>
                                  <div class="text-fade">
                                    This transfer will be settled in next
                                    available settlement slot
                                  </div>
                                </div>
                              </div>
                            </label>
                          </div>
                          {this.state.errors &&
                            <div>
                              {this.state.errors.map((item, key) => {
                                return (
                                  <Alert
                                    key={key}
                                    type="error"
                                    message={item}
                                  />
                                );
                              })}
                            </div>}
                          <div class="btn-toolbar text-center">
                            <button
                              type="button"
                              className="btn btn-default btn-half"
                              onClick={this.onDismiss}
                            >
                              Discard
                            </button>
                            <AsyncButton
                              type="submit"
                              className="btn btn-primary btn-half"
                              text="Save"
                              pendingText="Saving..."
                              onClick={this.onSubmit}
                            />
                          </div>
                        </form>
                      : <span>
                          {this.state.onHold === 'false'
                            ? <span className="text-success">Scheduled</span>
                            : this.state.holdUntil
                              ? <span className="text-warning">
                                  Scheduled for&nbsp;
                                  <Time
                                    value={this.state.date.toDate() / 1000}
                                    format="Do MMM YYYY"
                                  />
                                </span>
                              : <span className="text-danger">On Hold</span>}
                          <span>&nbsp;&nbsp;</span>
                          <a href className="btn-link" onClick={this.onEdit}>
                            {'change'}
                          </a>
                        </span>}
                    {this.state.onHold === 'false' &&
                      <div className="text-fade">
                        Transfers scheduled to settle on bank holidays will get
                        settled on the next working day.
                      </div>}
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Source ID"
                    value={() =>
                      <div>
                        <Link to={`/payments/${transfer.source}`}>
                          {transfer.source}
                        </Link>
                      </div>}
                  />

                  <EntityDetailRow label="Reversal">
                    <TransferReversal
                      transfer={transfer}
                      reversals={reversals}
                      openTransferReversalModal={openReversalModal}
                    />
                  </EntityDetailRow>

                  {/* Notes */}
                  <EntityDetailRow label="Notes">
                    {transfer.notes &&
                      (Object.keys(transfer.notes).length === 0
                        ? '--'
                        : Object.keys(transfer.notes).map((key, index) =>
                            <div className="m-b" key={index}>
                              <Definition>
                                {key}
                                {String(transfer.notes[key])}
                              </Definition>
                            </div>
                          ))}
                  </EntityDetailRow>
                </div>
              </div>
            </div>}
      </div>
    );
  }
}
