import React from 'react';
import { PowerSelect } from 'react-power-select';
import { Error, Label } from './index';
import { classList } from 'common/utils/rzp-utils';

const ReactPowerSelect = ({ className, label, propagatedError, disabled, ...props }) => (
  <div
    className={classList(
      `Input Input--powerselect${disabled ? ' Input--disabled' : ''}`,
      className,
    )}
  >
    {label ? <Label text={label} /> : null}
    <PowerSelect
      placeholder={'--Select--'}
      searchPlaceholder={''}
      optionLabelPath="label"
      searchInputAutoFocus
      className="Input-content"
      {...props}
    />
    <Error text={propagatedError} />
  </div>
);

export default ReactPowerSelect;
