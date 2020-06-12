import React from 'react';
import Input from 'common/new-ui/Input';

export default props => {
  const { placeholder, meta, input, ...otherProps } = props;
  const hasError = meta.touched && meta.error;

  return (
    <Input
      defaultValue={input.value}
      value={input.value}
      onFocus={props.handleFocus}
      onChange={value => input.onChange(value)}
      placeholder={placeholder}
      {...otherProps}
    />
  );
};
