import ModalHeader from 'rzp/ui/ModalHeader';
import Button, { AsyncBtn } from 'component/Button';
import Input from 'component/Input';

import moment from 'moment';
import { dateCalculator } from 'component/Input/Calendar';
import { timeCalculator } from 'component/Input/Time';
import { isInteger } from 'rzp/utils/validators';

const expireByError = 'Expiry has passed';
const timesPayableError = 'Enter number greater than payments made';

export default class ActivateAgainModal extends React.Component {
  state = {
    expireBy: this.props.expireBy ? moment(this.props.expireBy * 1000) : null,
    hasNoExpiry: '0',
    timesPayable: this.props.timesPayable,
    hasNoLimit: '0',
  };

  componentDidMount() {
    this.toggleDisableState();

    this.flushExpireByError();
    this.flushTimesPayableError();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState() {
    /*
     * Fields like: 'Time Payable' is required on checkbox. So, if value not selected, html marks it as ':invalid' which is tehnically valid in our case.
     * Hence, relying on is-invalid.
     * */
    const invalidFields = document.querySelectorAll(
      '.ModalForm--ActivationAgain .Input.is-invalid'
    );
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  }

  onDateChange = date => {
    const curExpiryByTime = this.state.expireBy;

    dateCalculator(date, curExpiryByTime, this.updateDate);
  };

  onTimeChange = date => {
    const curDate = this.state.expireBy;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = ts => {
    const newDate = moment(ts);

    this.setState(
      {
        expireBy: newDate,
      },
      this.flushExpireByError
    );
  };

  flushExpireByError() {
    /*
    * New time must be greater than current time.
    * Ideally it must be atleast 15 min past current time. But in that case error won't be shown on FE,
    * but only calendar+time will be shown to be filled again.
    *
    * */

    const resetError =
      (this.state.hasNoExpiry == '0' && this.state.expireBy > moment()) ||
      this.state.hasNoExpiry == '1';

    this.setState({
      expireByError: resetError ? null : expireByError,
    });
  }

  flushTimesPayableError() {
    // Has no limit, or new times payable is more than times-paid
    const resetError =
      (this.state.hasNoLimit == '0' &&
        this.state.timesPayable > this.props.timesPaid) ||
      this.state.hasNoLimit == '1';

    this.setState({
      timesPayableError: resetError ? null : timesPayableError,
    });
  }

  editTimesPayable = e => {
    this.setState(
      {
        timesPayable: e.target.value,
      },
      this.flushTimesPayableError
    );
  };

  render() {
    let msg = [];

    this.props.expireBy && msg.push('change the expiry to a later date');
    this.props.timesPayable &&
      msg.push(
        'increase the max number of times you want to accept the payments'
      );

    msg = msg.join(' and ');

    return (
      <div>
        <ModalHeader
          title="Activate Page?"
          onCloseClick={this.props.handleClose}
        />

        <div class="modal-body">
          <p>
            Once you activate the page, you will be able to accept payments.{' '}
            {msg.length && 'Kindly '} {msg}.
          </p>

          <div class="ModalForm ModalForm--ActivationAgain">
            {this.props.expireBy && (
              <div class="ModalForm-field">
                <div class="Input-label">Expires On</div>
                <Input.Check
                  fieldLabel="No Expiry"
                  defaultValue="0"
                  value={this.state.hasNoExpiry}
                  onChange={e => {
                    this.setState(
                      {
                        hasNoExpiry: e.target.value,
                      },
                      this.flushExpireByError()
                    );
                  }}
                />
                <Input.Group class="InputGroup--near InputGroup--inline">
                  <div class="Input-content">
                    <Input.ToCalendar
                      data-name="expire_by_date"
                      placeholder="15-04-2018"
                      propagatedError={this.state.expireByError}
                      defaultValue={this.state.expireBy}
                      disabled={this.state.hasNoExpiry === '1'}
                      readOnly={true}
                      onChange={this.onDateChange}
                      size="half_small"
                      addonAfter={<i class="i i-date-range" />}
                      placement="topLeft"
                      allowToday={true}
                      disablePastDates={true}
                      mature={true}
                    />
                    {!!this.state.expireBy && (
                      <Input.TimePicker
                        placeholder="11:59PM"
                        defaultValue={this.state.expireBy}
                        disabled={this.state.hasNoExpiry === '1'}
                        readOnly={true}
                        onChange={this.onTimeChange}
                        size="half_small"
                        addonAfter={<i class="i i-time" />}
                      />
                    )}
                  </div>
                </Input.Group>
              </div>
            )}

            {this.props.timesPayable && (
              <div class="ModalForm-field">
                <div class="Input-label">Times Payable</div>

                <div class="InputGroup Input">
                  <Input.Check
                    fieldLabel="No Limit"
                    name="hasNoLimit"
                    defaultValue="0"
                    value={this.state.hasNoExpiry}
                    validator={val => {
                      if (!isInteger(val)) {
                        return 'Enter valid number';
                      }
                    }}
                    onChange={e => {
                      this.setState(
                        {
                          hasNoLimit: e.target.value,
                        },
                        this.flushTimesPayableError
                      );

                      if (e.target.value == '0') {
                        setTimeout(
                          () =>
                            document
                              .getElementsByName('times_payable')[0]
                              .focus(),
                          10
                        );
                      }
                    }}
                  />
                  <Input
                    name="times_payable"
                    class="Input"
                    placeholder="TimesPayable"
                    value={this.state.timesPayable}
                    disabled={this.state.hasNoLimit === '1'}
                    propagatedError={this.state.timesPayableError}
                    required={true}
                    onFocus={e => {
                      e.target.select();
                    }}
                    validator={() => {
                      if (
                        this.state.hasNoLimit === '0' &&
                        !this.state.timesPayable
                      ) {
                        return 'Please fill out this field';
                      }
                    }}
                    onChange={this.editTimesPayable}
                  />
                </div>
              </div>
            )}

            <div style={{ textAlign: 'center', marginTop: 40 }}>
              <Button onClick={this.props.handleClose}>No, don't</Button>
              <AsyncBtn.Primary
                style={{ marginRight: 0, marginLeft: 8 }}
                disabled={this.state.disableSubmit}
                onClick={() => {
                  const reqPayload = {};

                  if (this.props.expireBy) {
                    reqPayload.expire_by =
                      this.state.hasNoExpiry == '1'
                        ? null
                        : Math.floor(this.state.expire_by / 1000);
                  }

                  if (this.props.timesPayable) {
                    reqPayload.times_payable =
                      this.state.hasNoLimit == '1'
                        ? null
                        : Number(this.state.timesPayable);
                  }

                  return this.props.handleClick(reqPayload).then(resp => {
                    if (resp.data) {
                      this.props.handleClose();
                    }
                  });
                }}
                pendingState="Activating.."
              >
                Yes, activate
              </AsyncBtn.Primary>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
