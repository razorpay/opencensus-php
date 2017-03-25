import { Component } from 'react'
import { DateRangePicker } from 'react-dates'
import moment from 'moment'

export default class DateRangePickerField extends Component {
  state = {
    focused: null,
    from: this.props.startDate,
    to: this.props.endDate
  }

  constructor(props) {
    super(props)
  }

  onDatesChange = (dates)=> {
    this.setState({
      from: dates.startDate,
      to: dates.endDate
    })
    if (this.props.onDatesChange) {
      this.props.onDatesChange(dates)
    }
  }

  onFocusChange = (focused)=> {
    this.setState({
      focused: focused
    })
  }

  render() {
    let {
      style,
      startDate,
      endDate,
      onDatesChange,
      ...otherProps
    } = this.props

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
          {...otherProps}
        />
      </div>
    )
  }
}

DateRangePickerField.defaultProps = {
  displayFormat: 'DD MMM YYYY'
}
