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

    let date = (input.value && moment(input.value, 'D/M/Y')) || ''

    return (
      <SingleDatePicker
        id={name}
        date={date}
        focused={focused}
        isOutsideRange={isOutsideRange}
        onDateChange={input.onChange}
        onFocusChange={this.handleFocusChange}
        {...otherProps}
      />
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
