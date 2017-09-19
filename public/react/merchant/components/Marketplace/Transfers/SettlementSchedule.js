import AsyncButton from 'react-async-button';
import moment from 'moment';
import React, { Component } from 'react';

import DatePickerField from 'rzp/ui/Forms/DatePickerField';
import { Field } from 'redux-form';
import { isHoliday } from 'rzp/utils/bankHolidays';
import RadioButton from 'rzp/ui/Forms/RadioButton';

export default class SettlementSchedule extends Component {
  constructor(props) {
    super(props);

    const { transfer, onHold, holdUntil } = props;

    this.state = {
      onHold,
      holdUntil,
    };

    if (transfer) {
      this.state = {
        onHold: transfer.on_hold
          ? transfer.on_hold_until ? 'on_hold_until' : 'on_hold'
          : null,
        holdUntil: transfer.on_hold_until
          ? moment((transfer.on_hold_until + 600) * 1000)
          : null,
      };
    }
  }

  componentWillReceiveProps(nextProps) {
    this.setState({
      onHold: nextProps.onHold,
      holdUntil: nextProps.holdUntil
        ? moment(nextProps.holdUntil * 1000)
        : null,
    });
  }

  render() {
    return (
      <div>
        <Field
          component={RadioButton}
          name="onHold"
          htmlValue="on_hold_until"
          checked={this.state.onHold === 'on_hold_until'}
          label={_ =>
            <div>
              <span>Schedule settlement on</span>
            </div>}
        />
        <div className="transfers-onhold-datepicker">
          <Field
            component={DatePickerField}
            name="holdUntil"
            date={this.state.holdUntil}
            required
            disabled={
              this.state.onHold === null || this.state.onHold === 'on_hold'
            }
            isDayBlocked={date => {
              const dateWithOffset = moment()
                  .startOf('day')
                  .add(3, 'days')
                  .toDate(),
                currDate = date.clone().startOf('day').toDate();

              return currDate < dateWithOffset || isHoliday(date.toDate());
            }}
          />
        </div>
        <Field
          component={RadioButton}
          name="onHold"
          htmlValue="on_hold"
          checked={this.state.onHold === 'on_hold'}
          label={_ =>
            <div>
              <span>Put on hold</span>
              <div className="text-fade">
                The settlement will be on hold till specified otherwise.
              </div>
            </div>}
        />
        {this.props.transfer &&
          <div className="btn-toolbar text-center m-t">
            {typeof this.props.onDiscard === 'function' &&
              <button
                className="btn btn-default btn-half"
                onClick={this.props.onDiscard()}
              >
                Discard
              </button>}
            {typeof this.props.onSave === 'function' &&
              <AsyncButton
                className="btn btn-primary btn-half"
                text="Save"
                pendingText="Saving..."
                onClick={this.props.onSave({ ...this.state })}
              />}
          </div>}
      </div>
    );
  }
}
