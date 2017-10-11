import React, { Component } from 'react';

function focusNext(e) {
  e.target.nextElementSibling.focus();
}

function clickPrev(e) {
  e.target.previousElementSibling.click();
}

export default function Field({ label, ...props }) {
  return (
    <div class="field">
      <label onClick={focusNext}>{label}</label>
      <input {...props} />
    </div>
  );
}

export function CheckField({ label, ...props }) {
  return (
    <div class="field">
      <input {...props} type="checkbox" />
      <label onClick={clickPrev}>{label}</label>
    </div>
  );
}

export function SelectField({ label, children, ...props }) {
  return (
    <div class="field select-field">
      <label onClick={focusNext}>{label}</label>
      <select {...props}>{children}</select>
    </div>
  );
}
