import React, { Component } from 'react';

export default function Field(props) {
  return (
    <div class="pure-control-group">
      <label for={props.name}>{props.label}</label>
      <input {...props} />
    </div>
  );
}

export function Select(props) {
  return (
    <div class="pure-control-group">
      <label for={props.name}>{props.label}</label>
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
