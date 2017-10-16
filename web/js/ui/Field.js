import React, { Component } from 'react';
import { methods } from 'util/data';

function focusInput(e) {
  e.target.nextElementSibling.focus();
}

function toggleChecked(e) {
  var sib = e.target.nextElementSibling;
  sib.checked = !sib.checked;
}

export default function Field({ label, ...props }) {
  return (
    <div class="field">
      <label onClick={focusInput}>{label}</label>
      <input {...props} />
    </div>
  );
}

export const DateTimeField = props => (
  <Field {...props} type="datetime-local" />
);
export const FromField = _ => <DateTimeField name="from" label="From" />;
export const ToField = _ => <DateTimeField labele="to" label="To" />;

export function CheckField({ label, ...props }) {
  return (
    <div class="field">
      <label onClick={toggleChecked}>{label}</label>
      <input {...props} type="checkbox" />
    </div>
  );
}

export function SwitchField({ label, disabledValue, ...props }) {
  return (
    <div class="field switch-field">
      {disabledValue && (
        <input type="hidden" name={props.name} value={disabledValue} />
      )}
      <label onClick={toggleChecked}>{label}</label>
      <input {...props} type="checkbox" />
      <div class="switch-knob" />
    </div>
  );
}

export function SelectField({ label, children, ...props }) {
  return (
    <div class="field select-field">
      <label onClick={focusInput}>{label}</label>
      <select {...props}>{children}</select>
    </div>
  );
}

export function SelectMode({ defaultValue }) {
  return (
    <SelectField name="mode" label="Mode" defaultValue={defaultValue}>
      <option value="test">Test</option>
      <option value="live">Live</option>
    </SelectField>
  );
}

export function SelectMethod(props) {
  return (
    <SelectField name="method" label="Method" {...props}>
      <option value="" />
      {Object.keys(methods).map(m => (
        <option value={m} key={m}>
          {methods[m]}
        </option>
      ))}
    </SelectField>
  );
}
