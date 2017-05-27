import { Component } from 'react';
import { DateRangePicker } from 'react-dates';
import moment from 'moment';
import './DateRangePickerField.styl';

export default class DateRangePickerField extends Component {
  state = {
    focused: null,
    from: moment().endOf('day').subtract(30, 'days'),
    to: moment().endOf('day'),
  };

  componentWillMount() {
    this.props.onDatesChange({
      from: this.state.from.unix(),
      to: this.state.to.unix(),
      isLive: this.props.isLive,
    });
  }

  onDatesChange = dates => {
    this.setState(
      {
        from: dates.startDate,
        to: dates.endDate,
      },
      () => {
        if (
          !this.state.focused &&
          dates.startDate &&
          dates.endDate &&
          this.props.onDatesChange
        ) {
          this.props.onDatesChange({
            from: dates.startDate.unix(),
            to: dates.endDate.unix(),
            isLive: this.props.isLive,
          });
        }
      }
    );
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
        class={`daterangepicker-container ${this.state.focused ? 'datepicker--focused' : ''}`}
      >
        <i class="icon icon-date-range" />
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
