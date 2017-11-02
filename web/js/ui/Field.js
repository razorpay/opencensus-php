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

export default function Field({ label, onChange, value, infoMsg, ...props }) {
  return (
    <div class="field">
      <label onClick={focusInput}>{label}</label>
      <input {...props} value={value} onChange={onChange} />
      <div class="info">{infoMsg}</div>
    </div>
  );
}

export const DateTimeField = props => <Field {...props} type="date" />;
export const FromField = _ => <DateTimeField {..._} name="from" label="From" />;
export const ToField = _ => <DateTimeField {..._} name="to" label="To" />;

export function CheckField({ label, ...props }) {
  return (
    <div class="field">
      <label onClick={toggleChecked}>{label}</label>
      <input {...props} type="checkbox" />
    </div>
  );
}

export function SwitchField({ label, ...props }) {
  return (
    <div class="field">
      <label>{label}</label>
      <Switch knob {...props} />
    </div>
  );
}

export class Switch extends Component {
  disabledValue = this.props.disabledValue;
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

export function SelectField({ label, children, ...props }) {
  return (
    <div class="field select-field">
      <label onClick={focusInput}>{label}</label>
      <select {...props}>{children}</select>
    </div>
  );
}

export function TextAreaField({ label, children, ...props }) {
  return (
    <div class="field text-area-field">
      <label onClick={focusInput}>{label}</label>
      <textarea {...props}>{children}</textarea>
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

export function FileField({ label, many = false, infoMsg, ...props }) {
  return (
    <div className="field file-field">
      <label onClick={focusInput}>{label}</label>
      <input type="file" multiple={many} {...props} />
      <div class="info">{infoMsg}</div>
    </div>
  );
}
