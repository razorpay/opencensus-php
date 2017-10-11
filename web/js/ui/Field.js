import React, { Component } from 'react';

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
