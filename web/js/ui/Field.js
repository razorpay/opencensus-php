import React, { Component } from 'react';

function focusNext(e) {
  e.target.nextElementSibling.focus();
}

function clickPrev(e) {
  e.target.previousElementSibling.click();
}

export default function Field(props) {
  return (
    <div class="field">
      <label onClick={focusNext}>{props.label}</label>
      <input {...props} />
    </div>
  );
}

export function CheckField(props) {
  return (
    <div class="field">
      <input {...props} type="checkbox" />
      <label onClick={clickPrev}>{props.label}</label>
    </div>
  );
}

export function SelectField(props) {
  return (
    <div class="field select-field">
      <label onClick={focusNext}>{props.label}</label>
      <select
        defaultValue={props.defaultValue}
        required={props.required}
        name={props.name}
      >
        {props.children}
      </select>
    </div>
  );
}
