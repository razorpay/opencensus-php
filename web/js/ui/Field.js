import React, { Component } from 'react';
import { methods } from 'util/data';
import { prevent } from 'util/index';

function focusInput(e) {
  e.target.nextElementSibling.focus();
}

function toggleChecked(e) {
  var sib = e.target.parentNode.querySelector('input');
  sib.checked = !sib.checked;
}

export default function Field({
  tag = 'input',
  label,
  infoMsg,
  required,
  ...props
}) {
  let Tag = tag;
  return (
    <div class="field">
      <label class={required ? 'required' : ''} onClick={focusInput}>
        {label}
      </label>
      <Tag {...props} />
      {infoMsg && <div class="info">{infoMsg}</div>}
    </div>
  );
}

export const SelectField = _ => <Field {..._} tag="select" />;
export const TextAreaField = _ => <Field {..._} tag="textarea" />;
export const FileField = _ => <Field {..._} type="file" />;
export const DateTimeField = _ => <Field {..._} type="date" />;
export const DataListField = _ => <Field {..._} tag="datalist" />;

export const FromField = _ => <DateTimeField {..._} name="from" label="From" />;
export const ToField = _ => <DateTimeField {..._} name="to" label="To" />;

export function TimeField({ label, required, ...props }) {
  return (
    <div class="field">
      {label && (
        <label class={required ? 'required' : ''} onClick={toggleChecked}>
          {label}
        </label>
      )}
      <input type="time" name="start_at_time" {...props} />
    </div>
  );
}

export function RadioField({ label, value, defaultValue, ...props }) {
  return (
    <div class="field">
      <input
        type="radio"
        id={value}
        value={value}
        {...props}
        defaultChecked={defaultValue === value}
      />
      <label for={value}>{label}</label>
    </div>
  );
}

export function CheckField({ label, required, ...props }) {
  return (
    <div class="field">
      <label class={required ? 'required' : ''} onClick={toggleChecked}>
        {label}
      </label>
      <input {...props} type="checkbox" />
    </div>
  );
}

export function SwitchField({
  label,
  disabledLabel,
  enabledLabel,
  required,
  ...props
}) {
  return (
    <div class="field">
      <label class={required ? 'required' : ''}>{label}</label>

      {disabledLabel && <span>{disabledLabel}</span>}
      <Switch knob {...props} />
      {enabledLabel && <span>{enabledLabel}</span>}
    </div>
  );
}

export class Switch extends Component {
  disabledValue = this.props.disabledValue || '0';
  enabledValue = this.props.enabledValue || '1';
  buttonClass = this.props.knob ? 'checkbox knob' : 'checkbox';

  state = {
    checked: this.enabledValue === this.props.value,
  };

  toggle = e => {
    var checked = !this.state.checked;
    let onChange = this.props.onChange;
    let target = e.target;

    this.setState({ checked }, _ => onChange && onChange({ target }));
    prevent(e);
  };

  render() {
    let {
      knob,
      defaultChecked,
      disabledValue,
      enabledValue,
      ...restProps
    } = this.props;
    let { checked } = this.state;

    let buttonClass = this.buttonClass;
    if (checked) {
      buttonClass += ' checked';
    }

    return (
      <button
        {...restProps}
        class={buttonClass}
        value={checked ? this.enabledValue : this.disabledValue}
        onClick={this.toggle}
      />
    );
  }
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
