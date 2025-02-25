import 'react-dates/initialize';
import React, { Component } from 'react';
import moment from 'moment';
import { DateRangePicker } from 'react-dates';

let numInstances = 1;

// eslint-disable-next-line react/no-unsafe
export default class DateRangePickerField extends Component {
  constructor(props) {
    super(props);

    this.state = {
      focused: props.defaultFocusedInput || null,
      from: props.startDate || moment().endOf('day').subtract(30, 'days'),
      to: props.endDate || moment().endOf('day'),
    };

    this.id = `drp-${numInstances++}`;
  }

  UNSAFE_componentWillMount() {
    this.props.onDatesChange({
      from: this.state.from.unix(),
      to: this.state.to.unix(),
    });
  }

  onDatesChange = (dates) => {
    this.setState(
      {
        from: dates.startDate,
        to: dates.endDate,
      },
      () => {
        if (dates.startDate && dates.endDate && this.props.onDatesChange) {
          this.props.onDatesChange({
            from: dates.startDate.unix(),
            to: dates.endDate.unix(),
          });
        }
      },
    );
  };

  onFocusChange = (focused) => {
    this.setState({ focused }, () => {
      if (this.props.onFocusChange) {
        this.props.onFocusChange(focused);
      }
    });
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    // eslint-disable-next-line react/no-access-state-in-setstate
    let startDate = this.state.from;
    // eslint-disable-next-line react/no-access-state-in-setstate
    let endDate = this.state.to;

    if (
      nextProps.startDate &&
      (!startDate || startDate.toDate() !== nextProps.startDate.toDate())
    ) {
      startDate = nextProps.startDate;
    }

    if (nextProps.endDate && (!endDate || endDate.toDate() !== nextProps.endDate.toDate())) {
      endDate = nextProps.endDate;
    }

    this.setState({
      from: startDate,
      to: endDate,
    });
  }

  render() {
    const { style, startDate, endDate, onDatesChange, onFocusChange, ...otherProps } = this.props;

    const from = this.state.from;

    return (
      <div className={`daterangepicker-container ${this.state.focused ? 'datepicker--focused' : ''}`}>
        <i className="i i-date-range" />
        <DateRangePicker
          startDateId={`${this.id}-startdate`}
          endDateId={`${this.id}-enddate`}
          startDate={this.state.from}
          endDate={this.state.to}
          onDatesChange={this.onDatesChange}
          onFocusChange={this.onFocusChange}
          focusedInput={this.state.focused}
          isOutsideRange={(day) => moment().isBefore(day)}
          initialVisibleMonth={(_) => from}
          hideKeyboardShortcutsPanel={true}
          readOnly={true}
          {...otherProps}
        />
        <span className="caret" />
      </div>
    );
  }
}

DateRangePickerField.defaultProps = {
  displayFormat: 'DD MMM YYYY',
};
