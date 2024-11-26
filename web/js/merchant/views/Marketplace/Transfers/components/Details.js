import AsyncButton from 'react-async-button';
import { Component } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';
import ShowWhen from 'merchant/components/ShowWhen';
import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import { SingleDatePicker } from 'react-dates';
import { nextWorkingDay, isHoliday } from 'common/utils/bankHolidays';
import { titleCase } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Fee from './Fee';
import TransferReversal from 'merchant/views/Marketplace/Transfers/components/TransferReversal';
import TransferSource from './TransferSource';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import { updateTranferInList } from 'merchant/reducers/marketplace/transfers/list';
import CustomClipboard from 'common/ui/Clipboard/Custom';

let initialState = {
  onHold: 'false',
  holdUntil: null,
  date: null,
  focused: false,
  dateError: null,
  errors: null,
  editView: false,
};

const ERROR_CODE_CTAS_MAP = {
  BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT: 'Please create another transfer.',
  BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE: (
    <span>
      Please <Link to="/addfunds">add funds</Link> to your account and then create a transfer.
    </span>
  ),
  INTERNAL_SERVER_ERROR:
    'Please create the transfer again. If the issue persists, please contact Razorpay Support.',
};

const SETTLEMENT_STATUS_COLOR_MAP = {
  settled: 'text-success',
};

const SettlementText = ({ data, transfer, onEdit }) => {
  let status;
  let showBusinessHolidaysInfo = false;
  let showChangeButton = false;

  const isSettlementOnHold = transfer.settlement_status === 'on_hold';
  const isSettlementOnHoldUntil = transfer.settlement_status === 'on_hold' && data.holdUntil;
  const isSettlementPending = transfer.settlement_status === 'pending';

  if (isSettlementOnHoldUntil || isSettlementPending) {
    showBusinessHolidaysInfo = true;
    showChangeButton = true;
  } else if (isSettlementOnHold) {
    showChangeButton = true;
  }

  if (isSettlementOnHoldUntil) {
    status = (
      <span>
        On Hold until <Time value={data.date.toDate() / 1000} format="Do MMM YYYY" />
      </span>
    );
  } else {
    status = (
      <span class={SETTLEMENT_STATUS_COLOR_MAP[transfer.settlement_status]}>
        {titleCase(transfer.settlement_status) || 'Not Applicable'}
      </span>
    );
  }

  return (
    <div>
      <div>
        {status}
        <span>&nbsp;&nbsp;</span>
        <ShowWhen
          additionalCondition={(user) => user.isAllowedEdit('payments') && showChangeButton}
        >
          <a href class="btn-link" onClick={onEdit}>
            Change
          </a>
        </ShowWhen>
      </div>
      {showBusinessHolidaysInfo && (
        <div class="text-fade">
          Transfers scheduled to settle on bank holidays will get settled on the next working day.
        </div>
      )}
      {transfer.recipient_settlement?.utr && (
        <div>
          <small className="m-r">UTR: {transfer.recipient_settlement.utr}</small>
          <span>
            <CustomClipboard value={transfer.recipient_settlement.utr}>
              <button className="btn btn-default btn-xs">Copy</button>
            </CustomClipboard>
          </span>
        </div>
      )}
    </div>
  );
};

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    updateTranferInList,
  },
)
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

  UNSAFE_componentWillReceiveProps(nextProps) {
    const transfer = nextProps.transfer;

    initialState = {
      ...initialState,
      onHold: (transfer.on_hold
        ? transfer.on_hold_until
          ? 'on_hold_until'
          : 'on_hold'
        : false
      ).toString(),
      holdUntil: transfer.on_hold_until,
      date: transfer.on_hold_until ? moment((transfer.on_hold_until + 600) * 1000) : null,
    };

    this.setState(initialState);
  }

  onDateChange(date) {
    this.setState({
      date,
      holdUntil: (date.startOf('day').toDate() - 600000) / 1000 || 0,
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
      return '';
    }

    const data = {};

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
      .then((response) => {
        initialState = {
          ...initialState,
          ...this.state,
          editView: false,
        };
        this.props.showNotification({
          type: 'success',
          message: 'Transfer schedule changed successfully',
        });
        this.setState(initialState);

        this.props.updateTranferInList(response.data);
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
    const { transfer, isLoading, openReversalModal, reversals, onClose } = this.props;
    const nextWorkingDate = nextWorkingDay(moment().startOf('day').toDate(), 3);

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
                <button type="button" class="close close-secondary" onClick={onClose}>
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Transfer ID: <strong>{transfer.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                {transfer.recipient_details && transfer.recipient_details.name && (
                  <EntityDetailRow label="Linked Account">
                    <Definition>
                      <span>{transfer.recipient_details.name}</span>
                      {transfer.recipient_details.email && (
                        <span>{transfer.recipient_details.email}</span>
                      )}
                      <code>{transfer.recipient}</code>
                    </Definition>
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Amount">
                  <ContentToggler>
                    <Amount value={transfer.amount} currency={transfer.currency} />
                    <div class="m-t">
                      <Fee
                        totalFee={transfer.fees}
                        rzpFee={transfer.fees - transfer.tax}
                        tax={transfer.tax}
                        currency={transfer.currency}
                      />
                    </div>
                  </ContentToggler>
                </EntityDetailRow>

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time value={transfer.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                  )}
                />

                <EntityDetailRow label="Transfer Status">
                  <RouteTransfersStatusLabel status={transfer.status} />
                  {transfer.status === 'failed' && transfer.error?.description && (
                    <div class="text-danger m-t">{transfer.error.description}.</div>
                  )}
                  {transfer.status === 'failed' && ERROR_CODE_CTAS_MAP[transfer.error?.code] && (
                    <div class="text-danger">{ERROR_CODE_CTAS_MAP[transfer.error.code]}</div>
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Settlement Status">
                  {this.state.editView ? (
                    <form onSubmit={this.onSubmit} name="updatePaymentTransfer">
                      <p>
                        <b>Change settlement schedule</b>
                      </p>
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
                      <div class="transfers-onhold-datepicker">
                        <SingleDatePicker
                          id="holdUntil"
                          name="holdUntil"
                          numberOfMonths={1}
                          disabled={this.state.onHold !== 'on_hold_until'}
                          hideKeyboardShortcutsPanel={true}
                          readOnly={true}
                          isDayBlocked={(date) => {
                            date = date.clone().startOf('day').toDate();

                            return date < nextWorkingDate || isHoliday(date);
                          }}
                          date={this.state.date}
                          onDateChange={this.onDateChange}
                          focused={this.state.focused}
                          onFocusChange={({ focused }) => this.setState({ focused })}
                        />
                        {this.state.dateError && (
                          <div class="text-small text-danger text-right">
                            Please select a schedule date
                          </div>
                        )}
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
                                The settlement will be on hold till specified otherwise.
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
                            value="false"
                            checked={this.state.onHold === 'false'}
                            onChange={this.onScheduleChange}
                          />
                          <div>
                            <div class="RadioButton__button" />
                            <div class="RadioButton__label">
                              <span>Settle Now</span>
                              <div class="text-fade">
                                This transfer will be settled in next available settlement slot
                              </div>
                            </div>
                          </div>
                        </label>
                      </div>
                      {this.state.errors && (
                        <div>
                          {this.state.errors.map((item, key) => {
                            return <Alert key={key} type="error" message={item} />;
                          })}
                        </div>
                      )}
                      <div class="btn-toolbar text-center">
                        <button
                          type="button"
                          class="btn btn-default btn-half"
                          onClick={this.onDismiss}
                        >
                          Cancel
                        </button>
                        <AsyncButton
                          type="submit"
                          class="btn btn-primary btn-half"
                          text="Save"
                          pendingText="Saving..."
                          onClick={this.onSubmit}
                        />
                      </div>
                    </form>
                  ) : (
                    <SettlementText data={this.state} transfer={transfer} onEdit={this.onEdit} />
                  )}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Source ID"
                  value={() => (
                    <div>
                      <TransferSource source={transfer.source} initiatePoint="transfer-details" />
                    </div>
                  )}
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
                      : Object.keys(transfer.notes).map((key, index) => (
                          <div class="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(transfer.notes[key])}
                              {!!transfer.linked_account_notes &&
                                transfer.linked_account_notes.indexOf(key) > -1 && (
                                  <span>
                                    <i class="i i-info-outline" /> This note is shown to the linked
                                    account
                                  </span>
                                )}
                              <i />
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
