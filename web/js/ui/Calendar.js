import React, { Component } from 'react';
import Calendar from 'rc-calendar';
import DatePicker from 'rc-calendar/lib/Picker';
import MonthCalendar from 'rc-calendar/lib/MonthCalendar';
import enUS from 'rc-calendar/lib/locale/en_US';

function disabledDate(current) {
  if (!current) {
    return false; // allow empty select
  }
  const date = moment();
  date.hour(0);
  date.minute(0);
  date.second(0);

  return current.year() < 2015 || current.valueOf() > date.valueOf(); // can not select days today onwards
}

export default class CalendarPicker extends Component {
  constructor(props) {
    super(props);

    this.state = {
      disabled: false,
      value: props.defaultValue,
    };
  }

  getFormat() {
    if (this.props.format) {
      return this.props.format;
    }

    var format = this.props.type === 'month' ? 'DD/MM' : 'DD/MM/YYYY';
    return format;
  }

  onChange = value => {
    this.props.onDayChange && this.props.onDayChange(value);
    this.setState({
      value,
    });
  };

  render() {
    const state = this.state;

    let calendar;

    if (this.props.type === 'month') {
      calendar = (
        <MonthCalendar
          locale={enUS}
          style={{ zIndex: 1000 }}
          disabledDate={disabledDate}
        />
      );
    } else {
      calendar = (
        <Calendar
          locale={enUS}
          style={{ zIndex: 1000 }}
          disabledTime={null}
          timePicker={null}
          defaultValue={this.props.defaultCalendarValue}
          showDateInput={false}
          disabledDate={disabledDate}
        />
      );
    }
    return (
      <DatePicker
        animation="slide-up"
        disabled={state.disabled}
        calendar={calendar}
        value={state.value}
        onChange={this.onChange}
      >
        {({ value }) => {
          return (
            <span tabIndex="0">
              <input
                name={this.props.name}
                placeholder={this.props.placeholder}
                disabled={state.disabled}
                readOnly
                tabIndex="-1"
                className="ant-calendar-picker-input ant-input"
                value={(value && value.format(this.getFormat())) || ''}
              />
            </span>
          );
        }}
      </DatePicker>
    );
  }
}
