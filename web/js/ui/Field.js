import React, { Component } from 'react';
import { methods } from 'common/data';
import { prevent } from 'common/util';
import moment from 'moment';
import { TypeAhead } from 'react-power-select';
import CalendarPicker from 'ui/Calendar';

function focusInput(e) {
  e.target.nextElementSibling.focus();
}

function toggleChecked(e) {
  var sib = e.target.parentNode.querySelector('input');
  sib.checked = !sib.checked;
}

const setDay = (date, timeOfDay) => moment(date)[timeOfDay]('day');

// helpMsg is same as infoMsg but with icon before the msg. (Names must be swapped)
export default function Field({
  tag = 'input',
  label,
  infoMsg,
  helpMsg,
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
      {(infoMsg || helpMsg) && (
        <div class="info-block">
          {helpMsg && <i class="i i-info-circle" />}
          {do {
            var msg = infoMsg || helpMsg;
            typeof msg === 'function' ? msg() : msg;
          }}
        </div>
      )}
    </div>
  );
}

export const SelectField = _ => <Field {..._} tag="select" />;
export const TextAreaField = _ => <Field {..._} tag="textarea" />;
export const FileField = _ => <Field {..._} type="file" />;

export const TimeField = _ => <Field {..._} type="time" />;
export const DataListField = _ => <Field {..._} tag="datalist" />;

export const DateField = ({
  label = '',
  fieldClass = '',
  onChange,
  component,
  ...props
}) => (
  <div class={`field ${fieldClass}`}>
    {label && <label class={props.required ? 'required' : ''}>{label}</label>}
    <CalendarPicker onDayChange={onChange} {...props} />
    <i class="post-field-icon i-date" />
    {component}
  </div>
);

export const FromField = _ => (
  <DateField
    name="from"
    label="From"
    postSelectionValue={val => val.startOf('day')}
    {..._}
  />
);

export const ToField = _ => (
  <DateField
    name="to"
    label="To"
    postSelectionValue={val => val.endOf('day')}
    {..._}
  />
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
      <label class={props.required ? 'required' : ''}>{label}</label>
      <input class="ui-checkbox" {...props} type="checkbox" />
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

class SearchableSelect extends Component {
  static defaultProps = {
    searchIndices: [],
    trackBy: 'value',
    options: [],
  };

  constructor({ options, trackBy, defaultValue }) {
    super();
    this.state = {
      selectedOption:
        options.find(option => option[trackBy] === defaultValue) || {},
    };
  }

  handleChange = ({ option }) => {
    this.setState({ selectedOption: option });
  };

  render() {
    const {
      options,
      required,
      searchIndices,
      label,
      name,
      trackBy,
      ...props
    } = this.props;

    return (
      <div class="searchable-select">
        <input
          type="hidden"
          class="hide"
          name={name}
          value={this.state.selectedOption[trackBy]}
          readOnly
        />
        <TypeAhead
          options={options}
          name={name}
          optionLabelPath="name"
          selected={this.state.selectedOption}
          onChange={this.handleChange}
          className="searchable-select-field"
          {...props}
        />
      </div>
    );
  }
}

export const SearchableSelectField = props => (
  <Field {...props} tag={SearchableSelect} />
);
