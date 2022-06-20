import React from 'react';
import moment from 'moment';
import Calendar from 'rc-calendar';
import DatePicker from 'rc-calendar/lib/Picker';
import MonthCalendar from 'rc-calendar/lib/MonthCalendar';
import enUS from 'rc-calendar/lib/locale/en_US';
import { classList } from 'common/utils/rzp-utils';

import { Label, Error, inputClass, separateDomProps, Description } from './index';
class CalendarWrapper extends React.Component {
  state = {
    value: this.props.defaultValue,
  };

  constructor(props) {
    super(props);
    this.disabedDateStrategy = this.getDisabledDateStrategy();
  }

  getDisabledDateStrategy = () => {
    if (this.props.allowAllDates) {
      return this.disabledInvalidDates;
    } else if (this.props.disabledDate) {
      if (typeof this.props.disabledDate === 'function') {
        return this.props.disabledDate;
      }
    } else if (this.props.disablePastDates) {
      return this.disabledPastDates;
    }
    return this.disabledFutureDates;
  };

  getFormat() {
    if (this.props.format) {
      return this.props.format;
    }

    const format = this.props.type === 'month' ? 'YYYY-MM' : 'DD-MM-YYYY';
    return format;
  }

  // value is moment object
  onChange = (value) => {
    // To modify the selected date from calendar, eg. endOf or startOf
    if (value && this.props.postSelectionValue) {
      value = this.props.postSelectionValue(value);
    }

    // Custom function to execute component specific functionality.
    this.props.onChange && this.props.onChange(value, this.props.name);

    this.setState({
      value,
    });
  };

  disabledInvalidDates = (current) => {
    if (!current) {
      return false; // allow empty select
    }
    const allowedTillYear = this.props.allowedPastTill || 2015;
    const isAllowedTill = current.year() < allowedTillYear;
    return isAllowedTill; // can not select future dates
  };

  disabledFutureDates = (current) => {
    if (!current) {
      return false; // allow empty select
    }
    current.endOf('day');

    const date = moment();
    date.endOf('day');

    const isBefore2015 = current.year() < (this.props.allowedPastTill || 2015);
    let isFuture;
    const diffInDays = current.diff(date, 'days');
    if (this.props.allowToday) {
      isFuture = diffInDays > 0;
    } else {
      isFuture = diffInDays >= 0;
    }

    return isBefore2015 || isFuture; // can not select future dates
  };

  disabledPastDates = (current) => {
    if (!current) {
      return false; // allow empty select
    }
    current.startOf('day');

    const date = moment();
    date.startOf('day');

    const isBefore2015 = current.year() < 2015;
    let isPast;
    const diffInDays = current.diff(date, 'days');
    if (this.props.allowToday) {
      isPast = diffInDays < 0;
    } else {
      isPast = diffInDays <= 0;
    }

    return isBefore2015 || isPast; // can not select past dates
  };

  onToggle = (open) => {
    if (!open) {
      return this.props.onBlur();
    }
    return false;
  };

  render() {
    const state = this.state;
    const allProps = separateDomProps(this.props);
    const { onBlur, className, innerRef, ...restDOMProps } = allProps.props; // onFocus and onBlur are not to be controllled by <input> here

    let calendar;

    if (this.props.type === 'month') {
      calendar = (
        <MonthCalendar
          locale={enUS}
          style={{ zIndex: 1000 }}
          disabledDate={this.disabedDateStrategy}
        />
      );
    } else {
      calendar = (
        <Calendar
          locale={enUS}
          style={{ zIndex: 1000 }}
          disabledTime={null}
          timePicker={null}
          defaultValue={this.state.value}
          showDateInput={true}
          showToday={false}
          showClear={false}
          disabledDate={this.disabedDateStrategy}
        />
      );
    }
    return (
      <DatePicker
        placement={this.props.placement || 'bottomLeft'}
        dropdownClassName={classList(
          'Input--Calendar-content',
          this.props.placement.indexOf('top') > -1 && 'Input--Calendar-content--top',
          className,
        )}
        animation="slide-up"
        disabled={this.props.disabled}
        onOpenChange={this.onToggle}
        calendar={calendar}
        value={state.value}
        showClear={false}
        onChange={this.onChange}
        onClear={this.onChange}
      >
        {({ value }) => {
          const uniqName = this.props.name || this.props['data-name'];

          const inputVal = value ? value.format(this.getFormat()) : '';

          return (
            <div className="Input-elWrapper" tabIndex="0">
              <input
                id={`${uniqName}-date-input`}
                value={inputVal}
                {...restDOMProps}
                className={classList(
                  'ant-calendar-picker-input ant-input Input-el',
                  this.props.addonAfter && 'Input-el--after',
                )}
                ref={innerRef}
              />
              {this.props.addonAfter && (
                <span className="Input-addons  Input-addons--after">{this.props.addonAfter}</span>
              )}
            </div>
          );
        }}
      </DatePicker>
    );
  }
}

class CalendarPicker extends React.Component {
  className = 'Input--Calendar';

  blur = (e) => {
    this.props.onBlur && this.props.onBlur(e);
  };

  render() {
    return (
      <div className={inputClass(this)}>
        <Label text={this.props.label} />
        <div className="Input-content">
          <CalendarWrapper onBlur={this.blur} {...this.props} />
          <Error text={this.props.propagatedError} />
          <Description text={this.props.description} />
        </div>
      </div>
    );
  }
}
export default React.forwardRef((props, ref) => <CalendarPicker {...props} innerRef={ref} />);
/*
 * Helper fn. to be for onChange for Input.CalendarPicker
 * */
export function dateCalculator(date, curSelectedTS, onCalculation) {
  if (date && date.target) {
    // Check if date is not of event type
    return;
  }

  let newSelectedTS;

  if (date) {
    let offsetTime = 0;

    if (curSelectedTS) {
      offsetTime = curSelectedTS.valueOf() - curSelectedTS.startOf('day').valueOf(); // Offset since start of day
    }

    newSelectedTS = offsetTime
      ? date.startOf('day').valueOf() + offsetTime
      : date.endOf('day').valueOf();
  } else {
    newSelectedTS = null;
  }

  if (!curSelectedTS && newSelectedTS) {
    setTimeout(() => {
      const ele = document.getElementsByName('expire_by')[0];
      ele && ele.focus();
    }, 100);
  }

  onCalculation(newSelectedTS);
}
