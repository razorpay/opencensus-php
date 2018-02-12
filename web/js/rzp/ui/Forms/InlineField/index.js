import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, formValueSelector } from 'redux-form';

@connect(state => ({
  state,
}))
class InlineField extends Component {
  componentWillMount() {
    this.selector = formValueSelector(this.props.formName);
  }

  render() {
    let {
      formName,
      normalizeValue,
      placeholder,
      rightAlign = false,
      ...otherProps
    } = this.props;
    let currentValue = this.selector(this.props.state, this.props.name);
    currentValue = normalizeValue(currentValue);

    return (
      <div
        class={`inlineField ${
          this.props.disabled ? 'inlineField--disabled' : ''
        } ${rightAlign ? 'inlineField--right' : 'inlineField--left'}`}
      >
        <Field {...otherProps} />

        <span
          class={`inlineField__value-container ${
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
      </div>
    );
  }
}

InlineField.defaultProps = {
  normalizeValue: value => value,
};

InlineField.propTypes = {
  normalizeValue: PropTypes.func,
  formName: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
};

export default InlineField;
