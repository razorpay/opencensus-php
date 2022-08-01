import React from 'react';
import { classList } from 'common/utils/rzp-utils';

const InputField = (props) => {
  const {
    input,
    validate,
    tagName = 'input',
    meta: { touched, submitFailed, error } = {},
    showInlineErrorText = true,
    validateOnChange = false,
    ...otherProps
  } = props;

  const InputComponent = tagName;
  const showError = showInlineErrorText && (submitFailed || (touched && validateOnChange)) && error;

  return (
    <div className={classList('InputField', submitFailed && error && 'InputField--error')}>
      <InputComponent {...input} {...otherProps} />
      {showError && <div className="InputField__ErrorText text-danger">{error}</div>}
    </div>
  );
};

export default InputField;
