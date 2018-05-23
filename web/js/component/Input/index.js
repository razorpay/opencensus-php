import FileUpload from 'merchant/components/File/Upload';
import { classList } from 'common/util';

function inputClass({ props, state, className }) {
  let wrapperClass = 'Input';

  if (props.required) {
    wrapperClass += ' Input--required';
  }

  if (props.disabled) {
    wrapperClass += ' Input--disabled';
  }

  if (props.size) {
    wrapperClass += ' Input--' + props.size;
  }

  if (props.className) {
    wrapperClass += ' ' + props.className;
  }

  if (className) {
    wrapperClass += ' ' + className;
  }

  if (state) {
    if (state.focus) {
      wrapperClass += ' is-focused';
    }

    if (state.mature) {
      wrapperClass += ' is-mature';
    }

    if (state.error) {
      wrapperClass += ' is-invalid';
    }
  }

  return wrapperClass;
}

function separateDomProps(props) {
  let {
    tag = 'input',
    label,
    description,
    options,
    defaultValue,
    addonBefore,
    addonAfter,
    validator,
    info,
    autoRender,
    ...rest
  } = props;

  return {
    tag,
    label,
    description,
    options,
    defaultValue,
    info,
    autoRender,
    props: rest,
  };
}

class Info extends React.Component {
  render() {
    const { text } = this.props;

    if (text) {
      return (
        <div class="Input-info">
          {typeof text === 'object' ? (
            <ul>
              {Object.keys(text).map(key => (
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

class Description extends React.Component {
  render() {
    const text = this.props.text;

    if (this.props.text) {
      return (
        <div class="Input-desc">
          {typeof text === 'function' ? text() : text}
        </div>
      );
    }
    return null;
  }
}

class Label extends React.Component {
  render() {
    const text = this.props.text;
    if (text) {
      return (
        <div class={this.props.className || 'Input-label'}>
          {typeof text === 'function' ? text() : text}
        </div>
      );
    }
    return null;
  }
}

class Error extends React.Component {
  render() {
    if (this.props.text) {
      return <div class="Input-error">{this.props.text}</div>;
    }
    return null;
  }
}

/**
 * General Field component
 * @props
 *  - {String/Fn, optional} `info` - Info can also be a Promise, pure function, or string. Fn. helps to change description on basis of value selected
 *  - {String/Fn, optional} `description` - Description can be a string or pure function. Fn. helps to change description on basis of value selected
 * */
export default class Field extends React.Component {
  state = {
    mature: this.props.mature,
    focus: false,
    error: '',
  };

  requiredError = 'Please fill out this field';
  patternError = 'Please enter valid value';

  shouldComponentUpdate(nextProps, nextState) {
    // Field is marked impure-component if it's to auto-render, cuz it's dependent on render of other field, then allow 'auto re-render'.
    if (this.props.autoRender === true || this.state !== nextState) {
      return true;
    }

    return false; // Pure Component by default will not re-render
  }

  // Will be called only in cases of impure-component fields
  componentWillUpdate(nextProps) {
    if (nextProps !== this.props) {
      this.valid();
    }
  }

  focus = e => {
    this.props.onFocus && this.props.onFocus(e);
    this.setState({ focus: true });
  };

  blur = e => {
    this.props.onBlur && this.props.onBlur(e);
    this.setState({ focus: false });

    // dirty = Touched the input field
    if (this.state.dirty) {
      this.setState({ mature: true });
    }
  };

  change = e => {
    this.valid();
    this.props.onChange && this.props.onChange(e);

    if (!this.state.dirty) {
      this.setState({ dirty: true });
    }

    // Change description on basis of value changed
    if (typeof this.props.description === 'function') {
      const descriptionString = this.props.description(e);

      this.setState({
        descriptionEle: descriptionString || null, // If set undefined, description might take set its content based on default data.
      });
    }

    // Change info on basis of value changed
    if (typeof this.props.info === 'function') {
      let info = this.props.info(e);
      let infoEle;

      if (info != null) {
        // Handle api based information
        if (info.then) {
          infoEle = '...'; // Dummy loader while resolving promise
          info
            .then(data => {
              this.setState({
                infoEle: data || null,
              });
            })
            .catch(err => {
              this.setState({
                infoEle: null,
              });
            });
        } else {
          // If props.info is simple function
          infoEle = info;
        }
      } else {
        infoEle = null; // Unset info if undefined/null.
      }

      this.setState({
        infoEle,
      });
    }
  };

  valid() {
    let {
      pattern,
      required,
      validator,
      requiredError,
      patternError,
    } = this.props;

    let el = this.el;
    let value = el.value;
    let validity = el.validity;
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
  }

  setRef = el => {
    this.el = el;
    if (el) {
      this.valid();
    }
  };

  render() {
    let allProps = separateDomProps(this.props);
    let InputTag = allProps.tag;

    let defaultValue = allProps.defaultValue;
    if (this.props.type === 'file') {
      defaultValue = undefined; // File input doesn't take defaultValue
    }

    let infoEle = allProps.info;
    let descriptionEle = allProps.description;

    // It is expected that info is passed as function only when onChange it's dependent on onChange
    if (typeof infoEle === 'function') {
      infoEle = this.state.infoEle; // infoEle must always rely on state as its content is dependent on onChange
    }

    // It is expected that description is passed as function only when onChange it's dependent on onChange
    if (typeof descriptionEle === 'function') {
      descriptionEle =
        this.state.descriptionEle !== undefined
          ? this.state.descriptionEle
          : this.props.description(); // description can rely on onChange, and also default data
    }

    let InputComponent = (
      <InputTag
        {...allProps.props}
        onFocus={this.focus}
        onBlur={this.blur}
        onChange={this.change}
        class="Input-el"
        defaultValue={defaultValue}
        ref={this.setRef}
      />
    );

    // TODO: Case to handle: Custom UI for file component to display indication that file is uploaded but can be changed since form is not locked
    // In case of form is disabled, file input must not be displayed
    if (this.props.type === 'file' && this.props.disabled) {
      InputComponent = (
        <div class="Input--file-upload">
          {allProps.defaultValue ? (
            <span>
              File Already uploaded <i class="i-done text-success" />
            </span>
          ) : (
            'File Not Uploaded'
          )}
        </div>
      );
    }

    return (
      <div class={inputClass(this)}>
        <Label text={allProps.label} />
        <div class="Input-content">
          <div
            class={classList(
              'Input-elWrapper',
              InputTag.toLowerCase() === 'select' && 'Select-elWrapper'
            )}
          >
            {InputComponent}
            <Info text={infoEle} />
          </div>
          <Error text={this.state.error} />
          <Description text={descriptionEle} />
        </div>
      </div>
    );
  }
}

class Check extends Field {
  className = 'Input--checkbox Input-content';

  toggle = e => {
    e.target.value = e.target.checked ? 1 : 0;
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  };

  checked = Boolean(Number(this.props.defaultValue));

  render() {
    let { label, description, info, props } = separateDomProps(this.props);

    return (
      <div class={inputClass(this)}>
        <label>
          <input
            {...props}
            defaultChecked={this.checked}
            class="Input-el"
            type="checkbox"
            onChange={this.toggle}
            disabled={this.props.disabled}
          />
          <div className="Input-checkbox" />
          <Label class="Input-inlineLabel" text={label} />
        </label>
        <Description text={description} />
      </div>
    );
  }
}

class Radio extends Field {
  className = 'Input--radio';
  state = {
    value: this.props.defaultValue || 0,
  };

  toggle = e => {
    let target = e.target;
    if (!this.props.name) {
      Array.prototype.forEach.call(
        target.parentNode.parentNode.querySelectorAll('input'),
        el => {
          if (el !== target) {
            el.checked = false;
          }
        }
      );
    }
    this.setState({
      value: target.value,
    });
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  };

  render() {
    let { label, description, options, props } = separateDomProps(this.props);

    let defaultValue = this.state.value;
    let selectedDescription;

    return (
      <div class={inputClass(this)}>
        <Label text={label} />
        <div class="Input-content">
          <div class="Input--radioLabels">
            {options.map((o, i) => {
              let stringOption = typeof o == 'string';
              let label = stringOption ? o : o.label;
              let value = i;
              if (!stringOption) {
                if (o.hasOwnProperty('value')) {
                  value = o.value;
                }
              }

              // important to have double equals in below line
              // as dom value is always string, but integer may be passed in JS
              let selected = value == defaultValue;
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
                  />
                  <div className="Input-radio" />
                  <Label text={label} class="Input-inlineLabel" />
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

Field.Textarea = _ => <Field {..._} tag="textarea" />;
Field.File2 = _ => {
  return <Field {..._} type="file" />;
};

Field.File = _ => {
  let {
    label,
    description,
    selectedDescription,
    defaultValue,
    ...props
  } = separateDomProps(_);

  return (
    <div class={inputClass({ props: _ })}>
      <Label text={label} />
      <div class="Input-content Input-File">
        <div class="Input-elWrapper">
          <FileUpload
            name={_.name}
            onBiggerFileSize={_ => {
              console.log('File size is bigger');
            }}
            onFileChange={_.onChange}
            defaultValue={_.defaultValue}
            disabled={_.disabled}
            accept={_._accept}
            showAcceptInfo={_._showAcceptInfo}
            showStagedFileStatus={_._showStagedFileStatus}
          />
        </div>
        <Description text={description} />
      </div>
    </div>
  );
};

Field.Time = _ => <Field {..._} type="time" />;

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

Field.Group = ({ children }) => <div class="InputGroup">{children}</div>;
