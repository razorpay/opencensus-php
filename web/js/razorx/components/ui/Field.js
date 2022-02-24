import React, { Component } from 'react';
import { methods, cardSteps, authTypes, authGateway } from 'razorx/helpers/data';
import { prevent, classList } from 'common/utils/rzp-utils';
import moment from 'moment';
import { PowerSelect, TypeAhead } from 'react-power-select';
import CalendarPicker from 'razorx/components/ui/Calendar';

function focusInput(e) {
  e.target.nextElementSibling.focus();
}

function toggleChecked(e) {
  const sib = e.target.parentNode.querySelector('input');
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
  const Tag = tag;

  return (
    <div className={`field ${fieldClass}`} style={props.style}>
      {label && (
        <label className={props.required ? 'required' : ''} onClick={focusInput}>
          {label}
        </label>
      )}
      <Tag {...props} />
      {icon && <i className={`post-field-icon ${icon}`} />}
      <HelpMsg infoMsg={infoMsg} helpMsg={helpMsg} />
    </div>
  );
}

export const SelectField = (_) => <Field {..._} tag="select" />;
export const TextAreaField = (_) => <Field {..._} tag="textarea" />;
export const FileField = (_) => <Field {..._} type="file" />;

export const TimeField = (_) => <Field {..._} type="time" />;
export const DataListField = (_) => <Field {..._} tag="datalist" />;

export const DateField = ({ label = '', fieldClass = '', onChange, component, ...props }) => (
  <div className={`field ${fieldClass}`}>
    {label && <label className={props.required ? 'required' : ''}>{label}</label>}
    <CalendarPicker onDayChange={onChange} {...props} />
    <i className="post-field-icon i-date" />
    {component}
  </div>
);

export const FromField = (_) => (
  <DateField name="from" label="From" postSelectionValue={(val) => val.startOf('day')} {..._} />
);

export const ToField = (_) => (
  <DateField name="to" label="To" postSelectionValue={(val) => val.endOf('day')} {..._} />
);

export function RadioField({ label, value, defaultValue, ...props }) {
  return (
    <div className="field">
      <input
        type="radio"
        id={value}
        value={value}
        {...props}
        defaultChecked={defaultValue === value}
      />
      <label htmlFor={value}>{label}</label>
    </div>
  );
}

export function CheckField({ label, children, fieldClass, ...props }) {
  return (
    <div className={classList('field', fieldClass)}>
      <label
        className={props.required ? 'required' : ''}
        htmlFor={`id-${props.name}`}
        style={{ cursor: 'pointer' }}
      >
        {label}
      </label>
      <input className="ui-checkbox" id={`id-${props.name}`} {...props} type="checkbox" />
      {children}
    </div>
  );
}

export function SwitchField({
  label,
  disabledLabel,
  enabledLabel,
  nocaption,
  infoMsg,
  helpMsg,
  ...props
}) {
  return (
    <div className={classList('field', props.disabled && 'disabled')}>
      {label && <label className={props.required ? 'required' : ''}>{label}</label>}

      {disabledLabel && (
        <span className={`${nocaption ? '' : 'caption'} m-r`}>{disabledLabel}</span>
      )}
      <Switch knob {...props} />
      {enabledLabel && <span className={`${nocaption ? '' : 'caption'} m-l`}>{enabledLabel}</span>}
      <HelpMsg infoMsg={infoMsg} helpMsg={helpMsg} />
    </div>
  );
}

export class Switch extends Component {
  disabledValue = this.props.disabledValue || '0';
  enabledValue = this.props.enabledValue || '1';
  buttonClass = this.props.knob ? 'checkbox knob' : 'checkbox';

  state = {
    checked: this.enabledValue == this.props.defaultValue || this.props.defaultChecked,
  };

  toggle = (e) => {
    // it's an actual click, not triggered syntheticmouseevent due to form submission
    if (e.pageX && e.pageY) {
      const checked = !this.state.checked;
      const onChange = this.props.onChange;
      const target = e.target;

      this.setState({ checked }, (_) => onChange && onChange({ target }));
    }
    prevent(e);
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.hasOwnProperty('value')) {
      this.setState({ checked: this.enabledValue == nextProps.value });
    }
  }

  render() {
    const { knob = true, disabledValue, enabledValue, ...restProps } = this.props;
    const { checked } = this.state;

    let buttonClass = this.buttonClass;
    if (checked) {
      buttonClass += ' checked';
    }

    return (
      <button
        {...restProps}
        className={buttonClass}
        value={checked ? this.enabledValue : this.disabledValue}
        onClick={this.toggle}
      />
    );
  }
}

class SearchableSelect extends Component {
  static defaultProps = {
    trackBy: 'value',
    options: [],
  };

  constructor(props) {
    super();
    this.state = {
      selectedOption: this.getDefaultOption(props),
    };
  }

  getDefaultOption = ({ options, trackBy, defaultValue }) => {
    return options.find((option) => option[trackBy] === defaultValue) || {};
  };

  handleChange = ({ option }) => {
    this.setState({ selectedOption: option });
  };

  handleKeyDown = (e) => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;
      this.props.onInput && this.props.onInput(val);
    }, 5);
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.defaultValue && nextProps.defaultValue !== this.state.selectedOption.value) {
      this.setState({
        selectedOption: this.getDefaultOption(nextProps),
      });
    }
  }

  render() {
    const {
      options,
      required,
      isSearchable = true,
      allowClear = true,
      label,
      name,
      trackBy,
      ...props
    } = this.props;

    return (
      <div className="searchable-select" style={props.selectStyleProps}>
        <input
          type="hidden"
          className="hide"
          name={name}
          value={this.state.selectedOption ? this.state.selectedOption[trackBy] : ''}
          readOnly
        />
        {isSearchable ? (
          <TypeAhead
            options={options}
            name={name}
            optionLabelPath="name"
            selected={this.state.selectedOption}
            onChange={this.handleChange}
            onKeyDown={this.handleKeyDown}
            {...props}
          />
        ) : (
          <PowerSelect
            options={options}
            name={name}
            searchEnabled={false}
            optionLabelPath="name"
            selected={this.state.selectedOption}
            onChange={this.handleChange}
            className={`${allowClear ? '' : 'no-cross'}`}
            {...props}
          />
        )}
      </div>
    );
  }
}

export const SearchableSelectField = (props) => <Field {...props} tag={SearchableSelect} />;

export function HelpMsg({ infoMsg, helpMsg }) {
  return infoMsg || helpMsg ? (
    <div className="info-block">
      {helpMsg && <i className="i i-info-circle" />}
      {do {
        const msg = infoMsg || helpMsg;
        typeof msg === 'function' ? msg() : msg;
      }}
    </div>
  ) : null;
}
