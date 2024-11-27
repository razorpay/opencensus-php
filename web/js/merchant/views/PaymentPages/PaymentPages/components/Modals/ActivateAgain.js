import React from 'react';

import ModalHeader from 'common/ui/ModalHeader';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';

import moment from 'moment';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';

const expireByError = 'Expiry has passed';

export default class ActivateAgainModal extends React.Component {
  state = {
    expireBy: this.props.expireBy ? moment(this.props.expireBy * 1000) : null,
    hasNoExpiry: '0',
  };

  componentDidMount() {
    this.toggleDisableState();

    this.flushExpireByError();
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
      '.ModalForm--ActivationAgain .Input.is-invalid',
    );
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  }

  onDateChange = (date) => {
    const curExpiryByTime = this.state.expireBy;

    dateCalculator(date, curExpiryByTime, this.updateDate);
  };

  onTimeChange = (date) => {
    const curDate = this.state.expireBy;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = (ts) => {
    const newDate = moment(ts);

    this.setState(
      {
        expireBy: newDate,
      },
      this.flushExpireByError,
    );
  };

  flushExpireByError() {
    /*
     * New time must be greater than current time.
     * Ideally it must be at least 15 min past current time. But in that case error won't be shown on FE,
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

  render() {
    let { title, description } = this.props;

    let msg = [];
    this.props.expireBy && msg.push('Kindly change the expiry to a later date');
    this.props.isCompleted &&
      msg.push('One or more items are not purchasable by the customer. Update stock information');

    msg = msg.join(' and ');

    if (!title) {
      title = 'Activate Page?';
    }

    if (!description) {
      description = 'Once you activate the page, you will be able to accept payments.';
    }

    return (
      <div>
        <ModalHeader title={title} onCloseClick={this.props.handleClose} />

        <div class="modal-body">
          <p>
            {!!msg.length && `${msg}.`}
            <br />
            <br />
            {description}
          </p>

          <div class="ModalForm ModalForm--ActivationAgain">
            {this.props.expireBy && (
              <div class="ModalForm-field">
                <div class="Input-label">Expires On</div>
                <Input.Check
                  fieldLabel="No Expiry"
                  defaultValue="0"
                  value={this.state.hasNoExpiry}
                  onChange={(e) => {
                    this.setState(
                      {
                        hasNoExpiry: e.target.value,
                      },
                      this.flushExpireByError,
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

            <div style={{ textAlign: 'center', marginTop: 40 }}>
              <Button onClick={this.props.handleClose}>No, don't</Button>
              <AsyncBtn.Primary
                style={{ marginRight: 0, marginLeft: 8 }}
                disabled={this.state.disableSubmit}
                onClick={() => {
                  const reqPayload = {};

                  if (this.props.expireBy) {
                    reqPayload.expire_by =
                      this.state.hasNoExpiry == '1' ? null : Math.floor(this.state.expireBy / 1000);
                  }

                  return this.props
                    .handleClick(reqPayload)
                    .then((resp) => {
                      if (resp.data) {
                        this.props.handleClose();
                      }
                    })
                    .catch(() => {}); // to avoid uncaught promise error
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
