import React from 'react';
import FileUpload from 'merchant/components/File/Upload';
import { classList } from 'common/utils/rzp-utils';

import CalendarPicker from './Calendar';
import TimePicker from './Time';
import DateTime from './DateTime';
import PairList from './PairList';
import EditablePairsList from './EditablePairList';
import PowerDropdown from './PowerDropdown';
import EnumList from './EnumList';
import CurrencySelect from './CurrencySelect';
import TextareaAutoResize from './TextareaAutoResize';
import CurrencyInput from './CurrencyInput';

export function inputClass({ props, state, className }) {
  let wrapperClass = 'Input';

  if (props.required) {
    wrapperClass += ' Input--required';
  }

  if (props.disabled) {
    wrapperClass += ' Input--disabled';
  }

  if (props.size) {
    wrapperClass += ` Input--${props.size}`;
  }

  if (props.className) {
    wrapperClass += ` ${props.className}`;
  }

  if (className) {
    wrapperClass += ` ${className}`;
  }

  if (state) {
    if (state.focus) {
      wrapperClass += ' is-focused';
    }

    if (state.mature) {
      wrapperClass += ' is-mature';
    }

    /*
     * 'propagatedError' is used to show api related errors.
     * It's developer's repsonsibility to flush 'propagatedError' -> on onChange, or as per requirement, else it'll always remain visible.
     * */
    if (state.error || props.propagatedError) {
      wrapperClass += ' is-invalid';
    }
  }

  return wrapperClass;
}

export function separateDomProps(props) {
  const {
    tag = 'input',
    label,
    fieldLabel,
    description,
    options,
    defaultValue,
    addonValueBefore,
    addonBefore,
    addonAfter,
    validator,
    checkboxMaskLabel,
    info,
    autoRender,
    allowToday,
    disablePastDates,
    postSelectionValue,
    isOutsideRange,
    showClearDate,
    startOfDayTimeStamp,
    placement,
    mature,
    propagatedError,
    setRef,
    extraChildren,
    showCharacterLength,
    descriptionClass,
    ...rest
  } = props;

  return {
    tag,
    label,
    fieldLabel,
    description,
    options,
    defaultValue,
    addonValueBefore,
    addonBefore,
    addonAfter,
    checkboxMaskLabel,
    info,
    autoRender,
    allowToday,
    disablePastDates,
    postSelectionValue,
    isOutsideRange,
    showClearDate,
    startOfDayTimeStamp,
    placement,
    mature,
    propagatedError,
    setRef,
    extraChildren,
    showCharacterLength,
    descriptionClass,
    props: rest,
  };
}

export class Info extends React.Component {
  render() {
    const { text } = this.props;

    if (text) {
      return (
        <div class="Input-info">
          {typeof text === 'object' ? (
            <ul>
              {Object.keys(text).map((key) => (
                <li key={key}>
                  <b>{key}:</b> {text[key]}
                </li>
              ))}
            </ul>
          ) : (
            text
          )}
        </div>
      );
    }
    return null;
  }
}

export class Description extends React.Component {
  render() {
    const text = this.props.text;
    if (this.props.text) {
      return (
        <div class={classList('Input-desc', this.props.className)}>
          {typeof text === 'function' ? text() : text}
        </div>
      );
    }
    return null;
  }
}

export class Label extends React.Component {
  render() {
    const text = this.props.text;
    if (text) {
      return (
        <div class={classList('Input-label', this.props.className)} {...this.props}>
          {typeof text === 'function' ? text() : text}
        </div>
      );
    }
    return null;
  }
}

export class Error extends React.Component {
  render() {
    if (this.props.text) {
      return <div class="Input-error">{this.props.text}</div>;
    }
    return null;
  }
}

// eslint-disable-next-line valid-jsdoc
/**
 * General Field component
 * @props
 *  - {String/Fn, optional} `info` - Info can also be a Promise, pure function, or string. Fn. helps to change description on basis of value selected
 *  - {String/Fn, optional} `description` - Description can be a string or pure function. Fn. helps to change description on basis of value selected
 * */
export default class Field extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      mature: props.mature,
      error: '',
      // eslint-disable-next-line react/no-unused-state
      focus: false,
      character_length:
        props.tag === 'textarea' && props.defaultValue ? props.defaultValue.length : 0,
    };
  }

  requiredError = 'Please fill out this field';
  patternError = 'Please enter valid value';

  // Note 'autoRender' is not to update the value. It's just to update non-value stuffs like : info, description, etc.
  // To update value of such use pure JS.
  shouldComponentUpdate(nextProps, nextState) {
    // Field is marked impure-component if it's to auto-render, cuz it's dependent on render of other field, then allow 'auto re-render'.
    if (this.props.autoRender === true || this.state !== nextState) {
      return true;
    }

    return false; // Pure Component by default will not re-render
  }

  // Will be called only in cases of impure-component fields
  componentWillReceiveProps(nextProps) {
    const curPropsKey = Object.keys(this.props);
    const nextPropsKey = Object.keys(nextProps);

    let isDifferent = false;

    // Skip comparison of function and children which changes on re-render if inline-fns are provided
    if (curPropsKey.length === nextPropsKey.length) {
      for (let i = 0; i < curPropsKey.length; i++) {
        const key = curPropsKey[i];

        if (
          ['function', 'object'].indexOf(typeof this.props[key]) === -1 &&
          this.props[key] !== nextProps[key]
        ) {
          isDifferent = true;
          break;
        }
      }
    }

    if (isDifferent && this.el) {
      this.valid();
    }
  }

  componentDidMount() {
    // On render, FE error will be shown upfront if value filled is not value.
    if (this.el && this.el.value) {
      // eslint-disable-next-line react/no-did-mount-set-state
      this.setState({
        mature: true,
      });
    }
  }

  focus = (e) => {
    if (this.props.onFocus) {
      this.props.onFocus(e);
    }
    // eslint-disable-next-line react/no-unused-state
    this.setState({ focus: true });

    this.updateInfo(e); // On focus, it must display information based on some value of self / other field.
  };

  blur = (e) => {
    if (this.props.onBlur) {
      this.props.onBlur(e, this.state.error);
    }
    // eslint-disable-next-line react/no-unused-state
    this.setState({ focus: false });

    /*
     * Setting mature shows the error. However, mature is done only when the field is touched and also, blurred.
     * So, error on mature is shown only when it has touched + blurred once.
     * */
    if (this.state.touched) {
      this.setState({ mature: true });
    }
  };

  change = (e) => {
    this.valid();
    if (this.props.showCharacterLength) {
      this.setCharacterLength();
    }

    if (this.props.onChange) {
      this.props.onChange(e);
    }

    if (!this.state.mature || !this.state.touched) {
      this.setState({ touched: true });
    }

    this.updateInfo(e); // On focus, it must display information based on some value of self / other field.
  };

  valid() {
    const { validator, requiredError, patternError } = this.props;

    const el = this.el;
    const value = el.value;
    const validity = el.validity;
    let error = '';

    if (validity.valueMissing) {
      error = requiredError || this.requiredError;
    } else if (validity.patternMismatch) {
      error = patternError || this.patternError;
    } else if (validator) {
      error = validator(value) || '';
      el.setCustomValidity(error);
    }

    this.setState({ error });
    // if(this.state.touched){
    // }
  }

  setCharacterLength() {
    const { showCharacterLength } = this.props;

    const value = this.el.value;
    if (showCharacterLength) {
      const character_length = showCharacterLength(value);

      if (character_length) {
        this.setState({ character_length });
      } else {
        this.setState({ character_length: 0 });
      }
    }
  }

  setRef = (el) => {
    this.el = el;
    if (el) {
      this.valid();
    }

    if (this.props.setRef) {
      this.props.setRef(el);
    }
  };

  /* Updates the info based on onFocus and onInfo */
  updateInfo(e) {
    let infoEle = this.props.info;

    // It is expected that info is passed as function only when onChange it's dependent on onChange
    if (typeof infoEle === 'function') {
      infoEle = infoEle(e);

      if (infoEle) {
        // If info is thenable to get real time info based on input
        if (infoEle.then) {
          infoEle
            .then((data) => {
              this.setState({
                infoEle: data || null,
              });
            })
            .catch(() => {
              this.setState({
                infoEle: null,
              });
            });

          infoEle = '...'; // Setting loading state
        }

        this.setState({
          infoEle,
        });
      }
    }
  }

  render() {
    const allProps = separateDomProps(this.props);
    const InputTag = allProps.tag;

    let defaultValue = allProps.defaultValue;
    if (this.props.type === 'file') {
      defaultValue = undefined; // File input doesn't take defaultValue
    }

    let infoEle = allProps.info;
    if (typeof infoEle === 'function') {
      infoEle = this.state.infoEle;
    }

    const descriptionEle = allProps.description;

    const InputComponent = (
      <InputTag
        {...allProps.props}
        onFocus={this.focus}
        onBlur={this.blur}
        onChange={this.change}
        class={classList(
          'Input-el',
          allProps.addonBefore && 'Input-el--before',
          allProps.addonAfter && 'Input-el--after',
        )}
        defaultValue={defaultValue}
        ref={this.setRef}
      />
    );

    return (
      <div class={inputClass(this)}>
        <Label text={allProps.label} className={classList('Input-label', this.props.labelClass)} />
        <div class="Input-content">
          {allProps.extraChildren}
          <div
            class={classList(
              'Input-elWrapper',
              InputTag.toLowerCase() === 'select' && 'Select-elWrapper',
            )}
          >
            {allProps.addonBefore && (
              <span class="Input-addons Input-addons--before">{allProps.addonBefore}</span>
            )}
            {allProps.addonValueBefore && (
              <span class="Input-valueBefore">{allProps.addonValueBefore}</span>
            )}
            {InputComponent}
            {allProps.addonAfter && (
              <span class="Input-addons Input-addons--after">{allProps.addonAfter}</span>
            )}
            {allProps.showCharacterLength && this.state.character_length && (
              <span className="character-length">{this.state.character_length}</span>
            )}
            <Info text={infoEle} />
          </div>
          <Error text={this.state.error || this.props.propagatedError} />
          <Description className={allProps.descriptionClass} text={descriptionEle} />
        </div>
      </div>
    );
  }
}

class Check extends Field {
  className = 'Input--checkbox';

  state = {
    value: this.props?.defaultValue,
  };

  toggle = (e) => {
    e.target.value = e.target.checked ? 1 : 0;

    this.setState({
      value: e.target.value,
    });

    if (this.props.onChange) {
      this.props.onChange(e);
    }
  };

  get checked() {
    return Boolean(Number(this.state.value));
  }

  render() {
    const { label, fieldLabel, description, props } = separateDomProps(this.props);
    return (
      <div class={inputClass(this)}>
        {label && (
          <Label text={label} className={classList('Input-label', this.props.labelClass)} />
        )}
        <div
          className={classList(
            'Input-content',
            this.props.extraClassName ? this.props.extraClassName : '',
          )}
        >
          <div class="Input-elWrapper">
            <label>
              <input
                {...props}
                defaultChecked={
                  typeof this.props.checked !== 'undefined' ? undefined : this.checked
                }
                class="Input-el"
                type="checkbox"
                onChange={this.toggle}
                disabled={this.props.disabled}
              />
              {this.props.checkboxMaskLabel ? (
                <span class="Input-checkbox--label btn-link no-padding">
                  {this.props.checkboxMaskLabel[+this.checked]}
                </span>
              ) : (
                <React.Fragment>
                  <div className="Input-checkbox" />
                  <Label
                    className="Input-inlineLabel"
                    text={fieldLabel}
                    {...this.props.labelProps}
                  />
                </React.Fragment>
              )}
            </label>
          </div>
          <Description text={description} />
        </div>
      </div>
    );
  }
}

class Radio extends Field {
  className = 'Input--radio';
  state = {
    value: this.props.noDefaultSelectedValue
      ? this.props.defaultValue
      : this.props.defaultValue || 0,
  };

  toggle = (e) => {
    const target = e.target;
    if (!this.props.name) {
      Array.prototype.forEach.call(target.parentNode.parentNode.querySelectorAll('input'), (el) => {
        if (el !== target) {
          el.checked = false;
        }
      });
    }
    this.setState({
      value: target.value,
    });

    if (this.props.onChange) {
      this.props.onChange(e);
    }
  };

  onBlur = (e) => {
    if (this.props.onBlur) {
      this.props.onBlur(e);
    }
  };

  render() {
    const { label, description, options, props } = separateDomProps(this.props);

    const defaultValue = this.state.value;
    let selectedDescription;

    return (
      <div class={inputClass(this)}>
        <Label text={label} className={classList('Input-label', this.props.labelClass)} />
        <div class="Input-content">
          <div class="Input--radioLabels">
            {options.map((o, i) => {
              const stringOption = typeof o == 'string';
              const labelInput = stringOption ? o : o.label;
              let value = i;
              if (!stringOption) {
                if (o.hasOwnProperty('value')) {
                  value = o.value;
                }
              }

              // important to have double equals in below line
              // as dom value is always string, but integer may be passed in JS
              const selected = value == defaultValue;
              if (selected) {
                selectedDescription = o.description;
              }

              return (
                <label key={i}>
                  <input
                    type="radio"
                    {...props}
                    defaultChecked={selected}
                    onChange={this.toggle}
                    value={value}
                    class="Input-el"
                    onBlur={this.onBlur}
                  />
                  <div className="Input-radio" />
                  <Label text={labelInput} className="Input-inlineLabel" />
                </label>
              );
            })}
          </div>
          <Description text={selectedDescription} />
          <Description text={description} />
        </div>
      </div>
    );
  }
}

Field.Radio = Radio;
Field.Check = Check;

Field.Textarea = (_) => <Field {..._} tag="textarea" />;
Field.File2 = (_) => {
  return <Field {..._} type="file" />;
};

Field.File = (_) => {
  const { label, description } = separateDomProps(_);

  return (
    <div class={inputClass({ props: _ })}>
      <Label text={label} />
      <div class="Input-content Input-File">
        <FileUpload
          name={_.name}
          maxSize={_.maxSize}
          onBiggerFileSize={_.onBiggerFileSize}
          onFileChange={_.onChange}
          defaultValue={_.defaultValue}
          disabled={_.disabled}
          accept={_._accept}
          showAcceptInfo={_._showAcceptInfo}
          showStagedFileStatus={_._showStagedFileStatus}
          onCloseClick={_.onCloseClick}
          fileName={_.fileName}
          downloadUrl={_.downloadUrl}
          showCloseBtn={_.showCloseBtn}
        />
        <Description text={description} />
      </div>
    </div>
  );
};

Field.Time = (_) => <Field {..._} type="time" />;

/*
 * Input type=select Component
 * Note: o.name is same as value for option
 * */
Field.Select = ({ options, ...props }) => (
  <Field {...props} tag="select">
    {options.map((o, i) => {
      if (typeof o === 'object') {
        return (
          <option key={o.name} value={o.name}>
            {o.label}
          </option>
        );
      }
      return (
        <option key={i} value={i === 0 ? '' : i}>
          {' '}
          {/* Keeping the first entry as empty string because generally 1st field is empty(invalid) in Select options*/}
          {o}
        </option>
      );
    })}
  </Field>
);

/* Fields to be shown visually closer than other fields in form*/
Field.Group = ({ label, className, children, labelClass, ...otherProps }) => {
  return (
    <div class={classList('InputGroup', className, inputClass({ props: otherProps }))}>
      <Label text={label} />
      {children}
    </div>
  );
};

Field.PairList = PairList;
Field.EditablePairsList = EditablePairsList;
Field.PowerDropdown = PowerDropdown;
Field.EnumList = EnumList;

const ToCalendar = React.forwardRef((props, ref) => (
  <CalendarPicker
    class="disable-past-year"
    postSelectionValue={(val) => val.endOf('day')}
    {...props}
    ref={ref}
  />
));

Field.CalendarPicker = CalendarPicker;
Field.ToCalendar = ToCalendar;
Field.TimePicker = TimePicker;
Field.DateTime = DateTime;
Field.CurrencySelect = CurrencySelect;
Field.CurrencyInput = CurrencyInput;
Field.DateTime = DateTime;

Field.TextareaAutoResize = TextareaAutoResize;
