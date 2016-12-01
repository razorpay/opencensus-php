import { Component, PropTypes } from 'react'
import { SingleDatePicker } from 'react-dates'
import moment from 'moment'

export default class DatePickerField extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      focused: false
    }
    this.handleFocusChange = ::this.handleFocusChange
  }

  handleFocusChange({ focused }) {
    this.setState({
      focused
    })
  }

  render() {
    let { focused } = this.state
    let {
      input,
      name,
      isOutsideRange = () => false,
      meta: { touched, error },
      ...otherProps
    } = this.props

    let date = (input.value && moment.unix(input.value, this.props.displayFormat)) || null

    return (
      <div class='datepicker-container'>
        <SingleDatePicker
          id={name}
          date={date}
          focused={focused}
          isOutsideRange={isOutsideRange}
          onDateChange={(date) => {
            input.onChange(date.unix())
          }}
          onFocusChange={this.handleFocusChange}
          {...otherProps}
        />
        <span
          class='picker-icon'
          onClick={() => {
            this.setState({
              focused: true
            })
          }}
        >
          <i class='fa fa-calendar'></i>
        </span>
      </div>
    )
  }
}

DatePickerField.defaultProps = {
  numberOfMonths: 1,
  enableOutsideDays: true,
  displayFormat: 'DD MMM YYYY'
}

DatePickerField.propTypes = {
  name: PropTypes.string.isRequired
}
