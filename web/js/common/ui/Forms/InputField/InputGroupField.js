import React from 'react';
import { classList } from 'common/utils/rzp-utils';

const InputGroupField = (props) => {
  const {
    input,
    validate,
    tagName = 'input',
    meta: { touched, submitFailed, error },
    showInlineErrorText = true,
    prefix,
    suffix,
    validateOnChange = false,
    ...otherProps
  } = props;

  const InputComponent = tagName;
  const showError = showInlineErrorText && (submitFailed || (touched && validateOnChange)) && error;

  return (
    <div className={classList('InputField', submitFailed && error && 'InputField--error')}>
      <div className="input-group">
        {prefix && <span className="input-group-addon">{prefix}</span>}
        <InputComponent {...input} {...otherProps} />
        {suffix && <span className="input-group-addon">{suffix}</span>}
      </div>

      {showError && <div className="InputField__ErrorText text-danger">{error}</div>}
    </div>
  );
};

export default InputGroupField;
