import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, formValueSelector } from 'redux-form'
import './InlineField.styl'

@connect(
  (state) => ({
    state
  })
)
class InlineField extends Component {
  componentWillMount() {
    this.selector = formValueSelector(this.props.formName)
  }

  render() {
    let {
      formName,
      normalizeValue,
      placeholder,
      leftAlign = false,
      ...otherProps
    } = this.props
    let currentValue = this.selector(this.props.state, this.props.name)
    currentValue = normalizeValue(currentValue)

    return (
      <div class='inlineField'>
        <Field
          {...otherProps}
        />

        <span class={`inlineField__value-container ${leftAlign ? 'left' : 'right'}`}>
          <span class={`${currentValue ? 'inlineField__value' : 'inlineField__placeholder'}`}>
            { currentValue || placeholder }
            { !this.props.disabled ? <i class='fa fa-pencil'></i> : '' }
          </span>
        </span>
      </div>
    )
  }
}

InlineField.defaultProps = {
  normalizeValue: (value) => value
}

InlineField.propTypes = {
  normalizeValue: PropTypes.func,
  formName: PropTypes.string,
  name: PropTypes.string
}

export default InlineField
