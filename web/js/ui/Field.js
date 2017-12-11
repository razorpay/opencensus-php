import React, { Component } from 'react';
import { methods } from 'util/data';
import { prevent } from 'util/index';
import { Input as DayPickerInput } from 'react-day-picker';
import moment from 'moment';

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
  fieldClass = '',
  icon,
  ...props
}) {
  if (tag === 'input' && !props.type) {
    props.type = 'text';
  }
  let Tag = tag;

  return (
    <div class={`field ${fieldClass}`}>
      <label class={props.required ? 'required' : ''} onClick={focusInput}>
        {label}
      </label>
      <Tag {...props} />
      {icon && <i class={`post-field-icon ${icon}`} />}
      {infoMsg && (
        <div class="info-block">
          {typeof infoMsg === 'function' ? infoMsg() : infoMsg}
        </div>
      )}
    </div>
  );
}

export const SelectField = _ => <Field {..._} tag="select" />;
export const TextAreaField = _ => <Field {..._} tag="textarea" />;
export const FileField = _ => <Field {..._} type="file" />;

const DateInput = ({
  onDayChange,
  dayPickerProps,
  format = 'DD/MM/YYYY',
  hideOnDayClick,
  value,
  ...props
}) => {
  return (
    <DayPickerInput
      format={format}
      formatDate={date => moment(date).format(format)}
      parseDate={input => moment(input, format).toDate()}
      placeholder={format}
      value={value}
      inputProps={props}
    />
  );
};
export const DateField = _ => (
  <Field type="text" {..._} tag={DateInput} icon={'i-date'} />
);
export const TimeField = _ => <Field {..._} type="time" />;
export const DataListField = _ => <Field {..._} tag="datalist" />;

export const FromField = _ => (
  <DateField {..._} name="from" label="From" placeholder="" />
);
export const ToField = _ => (
  <DateField {..._} name="to" label="To" placeholder="" />
);

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

export function CheckField({ label, children, ...props }) {
  return (
    <div class="field">
      <label class={props.required ? 'required' : ''} onClick={toggleChecked}>
        {label}
      </label>
      <input {...props} type="checkbox" />
      {children}
    </div>
  );
}

export function SwitchField({
  label,
  disabledLabel,
  enabledLabel,
  nocaption,
  ...props
}) {
  return (
    <div class="field">
      <label class={props.required ? 'required' : ''}>{label}</label>

      {disabledLabel && (
        <span class={`${nocaption ? '' : 'caption'} m-r`}>{disabledLabel}</span>
      )}
      <Switch knob {...props} />
      {enabledLabel && (
        <span class={`${nocaption ? '' : 'caption'} m-l`}>{enabledLabel}</span>
      )}
    </div>
  );
}

export class Switch extends Component {
  disabledValue = this.props.disabledValue || '0';
  enabledValue = this.props.enabledValue || '1';
  buttonClass = this.props.knob ? 'checkbox knob' : 'checkbox';

  state = {
    checked:
      this.enabledValue == this.props.defaultValue || this.props.defaultChecked,
  };

  toggle = e => {
    // it's an actual click, not triggered syntheticmouseevent due to form submission
    if (e.pageX && e.pageY) {
      var checked = !this.state.checked;
      let onChange = this.props.onChange;
      let target = e.target;

      this.setState({ checked }, _ => onChange && onChange({ target }));
    }
    prevent(e);
  };

  render() {
    let { knob = true, disabledValue, enabledValue, ...restProps } = this.props;
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

export function SelectMode({ defaultValue = 'live', ...props }) {
  return (
    <SelectField
      name="mode"
      label="Mode"
      defaultValue={defaultValue}
      {...props}
    >
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
