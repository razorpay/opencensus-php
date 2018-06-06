import moment from 'moment';
import Calendar from 'rc-calendar';
import Datetime from 'react-datetime';
import DatePicker from 'rc-calendar/lib/Picker';
import MonthCalendar from 'rc-calendar/lib/MonthCalendar';
import enUS from 'rc-calendar/lib/locale/en_US';
import { classList } from 'common/util';

import Field, { Label, inputClass, separateDomProps } from './index';

class CalendarWrapper extends React.Component {
  state = {
    value: this.props.defaultValue,
  };

  getFormat() {
    if (this.props.format) {
      return this.props.format;
    }

    var format = this.props.type === 'month' ? 'DD-MM' : 'DD-MM-YYYY';
    return format;
  }

  // value is moment object
  onChange = value => {
    // Custom function to execute component specific functionality.
    this.props.onChange && this.props.onChange(value);

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
      isPast = current.diff(date) < 0;
    } else {
      isPast = current.diff(date) <= 0;
    }

    const isBefore2015 = current.year() < 2015;
    return isBefore2015 || isPast; // can not select past dates
  };

  render() {
    const state = this.state;
    const allProps = separateDomProps(this.props);

    let calendar;

    if (this.props.type === 'month') {
      calendar = (
        <MonthCalendar
          className="Input--Calendar-content"
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
          className="Input--Calendar-content"
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
        disabled={this.props.disabled}
        calendar={calendar}
        value={state.value}
        showClear={true}
        onChange={this.onChange}
        onClear={this.onChange}
      >
        {({ value }) => {
          let uniqName = this.props.name || this.props['data-name'];

          let inpEle = document.getElementById(uniqName + '-date-input');
          if (inpEle) {
            if (value) {
              if (typeof value === 'object') {
                inpEle.value = value.format(this.getFormat());
              } else {
                inpEle.value = value;
                if (value.toString().length == 10) {
                  this.props.onChange &&
                    this.props.onChange(moment(value * 1000)); // Manual input
                }
              }
            } else {
              inpEle.value = '';
            }
          }

          return (
            <div class="Input-elWrapper" tabIndex="0">
              <input
                id={uniqName + '-date-input'}
                class={classList(
                  'ant-calendar-picker-input ant-input Input-el',
                  this.props.addonAfter && 'Input-el--after'
                )}
                {...allProps.props}
              />
              {this.props.addonAfter && (
                <span class="Input-addons  Input-addons--after">
                  {this.props.addonAfter}
                </span>
              )}
            </div>
          );
        }}
      </DatePicker>
    );
  }
}

export default class CalendarPicker extends React.Component {
  className = 'Input--Calendar';

  render() {
    return (
      <div class={inputClass(this)}>
        <Label text={this.props.label} />
        <div class="Input-content">
          <CalendarWrapper {...this.props} />
        </div>
      </div>
    );
  }
}

export class TimePicker extends React.Component {
  className = 'Input--TimePicker';
  state = {
    value:
      this.props.defaultValue && moment(this.props.defaultValue).format('LT'), // defaultValue is unix time stamp in ms -> Formatted to : 5:38 AM (() => {
  };

  onChange = value => {
    this.setState({ value });

    this.props.onChange && this.props.onChange(value);
  };

  render() {
    const allProps = separateDomProps(this.props);

    return (
      <div class={inputClass(this)}>
        <Label text={this.props.label} />
        <div class="Input-content">
          <div class="Input-elWrapper" tabIndex="0">
            <Datetime
              defaultValue={allProps.defaultValue}
              value={this.state.value}
              onChange={this.onChange}
              inputProps={{
                ...allProps.props,
                className: classList(
                  'Input-el',
                  this.props.addonAfter && 'Input-el--after'
                ),
              }}
              dateFormat={false}
              timeFormat={true}
            />
          </div>
          {this.props.addonAfter && (
            <span class="Input-addons  Input-addons--after">
              {this.props.addonAfter}
            </span>
          )}
        </div>
      </div>
    );
  }
}
