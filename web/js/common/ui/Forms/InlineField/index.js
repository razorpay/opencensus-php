import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, formValueSelector } from 'redux-form';

@connect(state => ({
  state,
}))
class InlineField extends Component {
  UNSAFE_componentWillMount() {
    this.selector = formValueSelector(this.props.formName);
  }

  render() {
    let {
      formName,
      normalizeValue,
      placeholder,
      rightAlign = false,
      keepValueInBG,
      valueInBGClass,
      ...otherProps
    } = this.props;
    let currentValue = this.selector(this.props.state, this.props.name);
    currentValue = normalizeValue(currentValue);

    let valueInBG = null;
    if (keepValueInBG) {
      valueInBG = (
        <span
          class={`${valueInBGClass} inlineField__value-container ${
            rightAlign ? 'right' : 'left'
          }`}
        >
          <span
            class={`${
              currentValue ? 'inlineField__value' : 'inlineField__placeholder'
            }`}
          >
            {currentValue || placeholder}
            {!this.props.disabled ? <i class="i i-edit" /> : ''}
          </span>
        </span>
      );
    }

    return (
      <div
        class={`inlineField ${
          this.props.disabled ? 'inlineField--disabled' : ''
        } ${rightAlign ? 'inlineField--right' : 'inlineField--left'}`}
      >
        <Field
          {...{ ...otherProps, placeholder: keepValueInBG ? '' : placeholder }}
        />
        {valueInBG}
      </div>
    );
  }
}

InlineField.defaultProps = {
  normalizeValue: value => value,
  keepValueInBG: false,
  valueInBGClass: '',
};

InlineField.propTypes = {
  normalizeValue: PropTypes.func,
  formName: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  keepValueInBG: PropTypes.bool,
  valueInBGClass: PropTypes.string,
};

export default InlineField;
