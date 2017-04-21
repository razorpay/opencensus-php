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
      onDateChange,
      meta: { touched, error },
      ...otherProps
    } = this.props;

    let date =
      (input.value && moment.unix(input.value, this.props.displayFormat)) ||
      null;

    return (
      <div
        class={`datepicker-container ${focused ? 'datepicker--focused' : ''}`}
      >
        <SingleDatePicker
          id={input.name}
          date={date}
          focused={focused}
          isOutsideRange={day => {
            let diff = moment().diff(day, 'hours') / 24;
            return !(diff <= 60 && diff >= 0);
          }}
          onDateChange={date => {
            input.onChange(date.unix());
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
};
