import FileUpload from 'merchant/components/File/Upload';
import { classList } from 'common/util';
import debounce from 'rzp/utils/debounce';

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
    fieldLabel,
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
    fieldLabel,
    description,
    options,
    defaultValue,
    addonBefore,
    addonAfter,
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
    if (nextProps !== this.props) {
      this.el && this.valid();
    }
  }

  componentDidMount() {
    // On change of every tab, FE error will be shown right in front if the value is filled but not valid
    if (this.el && this.el.value) {
      this.setState({
        mature: true,
      });
    }
  }

  focus = e => {
    this.props.onFocus && this.props.onFocus(e);
    this.setState({ focus: true });

    this.updateInfo(e); // On focus, it must display information based on some value of self / other field.
  };

  blur = e => {
    this.props.onBlur && this.props.onBlur(e);
    this.setState({ focus: false });
  };

  change = e => {
    this.valid();
    this.props.onChange && this.props.onChange(e);

    if (!this.state.mature) {
      this.setState({ mature: true });
    }

    this.updateInfo(e); // On focus, it must display information based on some value of self / other field.
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

          infoEle = '...'; // Setting loading state
        }

        this.setState({
          infoEle,
        });
      }
    }
  }

  render() {
    let allProps = separateDomProps(this.props);
    let InputTag = allProps.tag;

    let defaultValue = allProps.defaultValue;
    if (this.props.type === 'file') {
      defaultValue = undefined; // File input doesn't take defaultValue
    }

    let infoEle = allProps.info;
    if (typeof infoEle === 'function') {
      infoEle = this.state.infoEle;
    }

    let descriptionEle = allProps.description;

    let InputComponent = (
      <InputTag
        {...allProps.props}
        onFocus={this.focus}
        onBlur={this.blur}
        onChange={this.change}
        class={classList(
          'Input-el',
          allProps.addonBefore && 'Input-el--before',
          allProps.addonAfters && 'Input-el--after'
        )}
        defaultValue={defaultValue}
        ref={this.setRef}
      />
    );

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
            {allProps.addonBefore && (
              <span class="Input-addons Input-addons--before">
                {allProps.addonBefore}
              </span>
            )}
            {InputComponent}
            {allProps.addonAfter && (
              <span class="Input-addons  Input-addons--after">
                {allProps.addonAfter}
              </span>
            )}
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
  className = 'Input';

  toggle = e => {
    e.target.value = e.target.checked ? 1 : 0;
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  };

  checked = Boolean(Number(this.props.defaultValue));

  render() {
    let { label, fieldLabel, description, info, props } = separateDomProps(
      this.props
    );

    return (
      <div class={inputClass(this)}>
        {label && <Label text={label} />}
        <div class="Input-content Input--checkbox">
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
            <Label class="Input-inlineLabel" text={fieldLabel} />
          </label>
          <Description text={description} />
        </div>
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

    this.props.onChange && this.props.onChange(e);
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

Field.Group = ({ label, className, children }) => {
  let classes = typeof className === 'string' && className.split(' ');

  return (
    <div class={classList('InputGroup', classes)}>
      <Label text={label} />
      {children}
    </div>
  );
};

class Pair extends React.PureComponent {
  className = 'InputGroup';
  state = {
    maxAllowedPairs: this.props.maxAllowedPairs || 10,
    pairs: this.props.defaultValue || [],
  };

  onChange = debounce(this.props.onChange, 250);

  onAddNew = e => {
    const freshPairs = [...this.state.pairs];
    freshPairs.push({
      key: '',
      value: '',
    });

    this.setState({
      pairs: freshPairs,
    });

    setTimeout(
      () =>
        document
          .getElementsByName(`notes[${freshPairs.length - 1}][key]`)[0]
          .focus(),
      10
    );
  };

  updateField = (e, field) => {
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      const freshPairs = [...this.state.pairs];
      freshPairs[pairId][field] = e.target.value;

      this.onChange(freshPairs);

      this.setState({
        pairs: freshPairs,
      });
    }
  };

  updateKey = e => {
    this.updateField(e, 'key');
  };
  updateValue = e => {
    this.updateField(e, 'value');
  };

  removePair = e => {
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      let freshPairs = [...this.state.pairs];
      freshPairs.splice(pairId, 1);

      this.onChange(freshPairs);

      this.setState({
        pairs: freshPairs,
      });
    }
  };

  render() {
    const { label } = this.props;

    const tempStyle = !!this.state.pairs.length
      ? { borderLeft: '3px solid #f4f4f4', paddingLeft: 20 }
      : {};

    return (
      <div class={inputClass({ props: this.props })}>
        <Label text={label} />
        <div class="Input-content" style={tempStyle}>
          {!!this.state.pairs.length &&
            this.state.pairs.map((pair, idx) => (
              <div class="InputGroup" key={idx}>
                <div class="Input">
                  <div class="Input-elWrapper">
                    <input
                      class="Input-el Input-el--after"
                      name={`${this.props.name}[${idx}][key]`}
                      placeholder="Title (key)"
                      data-id={idx}
                      onChange={this.updateKey}
                      value={this.state.pairs[idx].key}
                    />
                    <span
                      class="Input-addons Input-addons--after Input-addons--clickable"
                      data-id={idx}
                      onClick={this.removePair}
                    >
                      <i class="i i-close text-danger" />
                    </span>
                  </div>
                </div>

                <div class="Input">
                  <div class="Input-elWrapper">
                    <textarea
                      class="Input-el"
                      name={`notes[${idx}][value]`}
                      placeholder="Description (value)"
                      data-id={idx}
                      onChange={this.updateValue}
                      value={this.state.pairs[idx].value}
                    />
                  </div>
                </div>
              </div>
            ))}

          {this.state.pairs.length < this.state.maxAllowedPairs ? (
            <button
              type="button"
              class="btn-link no-padding"
              onClick={this.onAddNew}
            >
              + Add New
            </button>
          ) : null}
        </div>
      </div>
    );
  }
}

Field.Pair = Pair;
