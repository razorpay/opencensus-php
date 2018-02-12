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

  // value is moment object
  onChange = value => {
    // Custom function to execute component specific functionality.
    this.props.onDayChange && this.props.onDayChange(value);

    // To modify the selected date from calendar, eg. endOf or startOf
    if(value && this.props.postSelectionValue) {
      value = this.props.postSelectionValue(value);
    }

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
          disabledDate={this.props.disabledDate || disabledDate}
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
          showDateInput={true}
          showToday={false}
          showClear={true}
          disabledDate={this.props.disabledDate || disabledDate}
        />
      );
    }
    return (
      <DatePicker
        animation="slide-up"
        disabled={state.disabled}
        calendar={calendar}
        value={state.value}
        showClear={true}
        onChange={this.onChange}
        onClear={this.onChange}
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
                required={this.props.required}
              />
            </span>
          );
        }}
      </DatePicker>
    );
  }
}
