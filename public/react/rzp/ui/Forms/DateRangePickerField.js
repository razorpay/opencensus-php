import { Component } from 'react';
import { DateRangePicker } from 'react-dates';
import moment from 'moment';

export default class DateRangePickerField extends Component {
  state = {
    focused: null,
    from: moment().endOf('day').subtract(30, 'days'),
    to: moment().endOf('day'),
  };

  componentWillMount() {
    this.props.onDatesChange({
      from: this.state.from,
      to: this.state.to,
    });
  }

  onDatesChange = dates => {
    let params = {
      from: dates.startDate,
      to: dates.endDate,
    };
    this.setState(params, () => {
      if (
        !this.state.focused &&
        dates.startDate &&
        dates.endDate &&
        this.props.onDatesChange
      ) {
        this.props.onDatesChange(params);
      }
    });
  };

  onFocusChange = focused => {
    this.setState(
      {
        focused: focused,
      },
      () => {
        if (this.props.onFocusChange) {
          this.props.onFocusChange(focused);
        }
      }
    );
  };

  render() {
    let {
      style,
      startDate,
      endDate,
      onDatesChange,
      onFocusChange,
      ...otherProps
    } = this.props;

    let from = this.state.from;

    return (
      <div
        class={`datepicker-container ${this.state.focused ? 'datepicker--focused' : ''}`}
        style={style}
      >
        <DateRangePicker
          startDate={this.state.from}
          endDate={this.state.to}
          onDatesChange={this.onDatesChange}
          onFocusChange={this.onFocusChange}
          focusedInput={this.state.focused}
          isOutsideRange={day => moment().isBefore(day)}
          initialVisibleMonth={_ => from}
          {...otherProps}
        />
      </div>
    );
  }
}

DateRangePickerField.defaultProps = {
  displayFormat: 'DD MMM YYYY',
};
