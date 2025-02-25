import React from 'react';

export default props => {
  let { label, input, meta, htmlValue, ...otherProps } = props;
  var inputValue = input.value;
  input.value = htmlValue;
  return (
    <div className="RadioButton">
      <label>
        <input
          type="radio"
          {...input}
          {...otherProps}
          checked={htmlValue === inputValue}
        />
        <div>
          <div className="RadioButton__button" />
          <div className="RadioButton__label">
            {typeof label === 'function' ? label() : label}
          </div>
        </div>
      </label>
    </div>
  );
};
