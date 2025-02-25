import Input from 'common/new-ui/Input';
import React from 'react';

export default function(props) {
  return (
    <Input.Group
      label={props.label}
      className="InputGroup--inline"
      required={props.required}
    >
      <div className="Input-content">
        <Input.CurrencySelect defaultValue={props.currency} />
        <Input
          name={props.name}
          onChange={props.onChange}
          placeholder={props.placeholder}
          type="Number"
          validator={props.validator}
          className={props.className}
        />
      </div>
    </Input.Group>
  );
}
