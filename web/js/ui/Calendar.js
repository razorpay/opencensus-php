import React, { Component } from 'react';
import Calendar from 'rc-calendar';
import DatePicker from 'rc-calendar/lib/Picker';
import MonthCalendar from 'rc-calendar/lib/MonthCalendar';
import enUS from 'rc-calendar/lib/locale/en_US';
import { disabledPastDates } from 'common/util';

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
    if (value && this.props.postSelectionValue) {
      value = this.props.postSelectionValue(value);
    }

    this.setState({
      value,
    });
  };

  disabledInvalidDates = current => {
    if (!current) {
      return false; // allow empty select
    }

    const isBefore2015 = current.year() < 2015;
    return isBefore2015; // can not select future dates
  };

  disabledFutureDates = current => {
    if (!current) {
      return false; // allow empty select
    }
    current.endOf('day');

    const date = moment();
    date.endOf('day');

    const isBefore2015 = current.year() < 2015;
    let isFuture;
    if (this.props.allowToday) {
      isFuture = current.diff(date) > 1;
    } else {
      isFuture = current.diff(date) >= 1;
    }

    return isBefore2015 || isFuture; // can not select future dates
  };

  disabledPastDates = current => {
    if (!current) {
      return false; // allow empty select
    }
    current.startOf('day');

    const date = moment();
    date.startOf('day');

    let isPast;
    if (this.props.allowToday) {
      isPast = current.diff(date) < 1;
    } else {
      isPast = current.diff(date) <= 1;
    }

    const isBefore2015 = current.year() < 2015;
    return isBefore2015 || isPast; // can not select past dates
  };

  render() {
    const state = this.state;

    let calendar;

    if (this.props.type === 'month') {
      calendar = (
        <MonthCalendar
          locale={enUS}
          style={{ zIndex: 1000 }}
          disabledDate={
            this.props.allowAllDates
              ? this.disabledInvalidDates
              : this.props.disablePastDates
                ? this.disabledPastDates
                : this.disabledFutureDates
          }
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
          disabledDate={
            this.props.allowAllDates
              ? this.disabledInvalidDates
              : this.props.disablePastDates
                ? this.disabledPastDates
                : this.disabledFutureDates
          }
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
