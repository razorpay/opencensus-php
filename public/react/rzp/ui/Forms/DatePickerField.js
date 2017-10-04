import { Component, PropTypes } from 'react';
import { SingleDatePicker } from 'react-dates';
import moment from 'moment';

export default class DatePickerField extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      focused: false,
    };
    this.handleFocusChange = ::this.handleFocusChange;
  }

  handleFocusChange({ focused }) {
    this.setState({
      focused,
    });
  }

  render() {
    let { focused } = this.state;
    let {
      input,
      name,
      endOfDayTimeStamp,
      startOfDayTimeStamp,
      onDateChange,
      isOutsideRange,
      outputDateFormat,
      meta: { touched, error },
      ...otherProps
    } = this.props;

    let dateFormatFn = outputDateFormat ? moment : moment.unix;
    let date = (input.value && dateFormatFn(input.value)) || null;

    return (
      <div
        class={`datepicker-container ${focused ? 'datepicker--focused' : ''}`}
      >
        <SingleDatePicker
          id={input.name}
          date={date}
          focused={focused}
          initialVisibleMonth={() =>
            date ? moment(date, 'MM YYYY') : moment()}
          isOutsideRange={isOutsideRange}
          onDateChange={date => {
            if (date) {
              if (endOfDayTimeStamp) {
                date = date.endOf('day');
              } else if (startOfDayTimeStamp) {
                date = date.startOf('day');
              }
              date = outputDateFormat
                ? date.format(outputDateFormat)
                : date.unix();
            }

            input.onChange(date);
            onDateChange(date);
          }}
          onFocusChange={this.handleFocusChange}
          {...otherProps}
        />
      </div>
    );
  }
}

DatePickerField.defaultProps = {
  numberOfMonths: 1,
  enableOutsideDays: true,
  displayFormat: 'DD MMM YYYY',
  onDateChange: () => {},
  isOutsideRange: () => false,
  endOfDayTimeStamp: false,
};
