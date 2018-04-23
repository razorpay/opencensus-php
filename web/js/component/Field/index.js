function inputClass({ props, state, className }) {
  let wrapperClass = 'Input';

  if (props.required) {
    wrapperClass += ' Input--required';
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
    ...rest
  } = props;

  return {
    tag,
    label,
    description,
    options,
    defaultValue,
    props: rest
  }
}

class Description extends React.PureComponent {
  render() {
    if (this.props.text) {
      return <div class='Input-desc'>{this.props.text}</div>
    }
    return null;
  }
}

class Label extends React.PureComponent {
  render() {
    if (this.props.text) {
      return <div class={this.props.className || 'Input-label'}>{this.props.text}</div>
    }
    return null;
  }
}

class Error extends React.PureComponent {
  render() {
    if (this.props.text) {
      return <div class='Input-error'>{this.props.text}</div>
    }
    return null;
  }
}

export default class Field extends React.PureComponent {
  state = {
    mature: this.props.mature,
    focus: false,
    error: ''
  }

  requiredError = 'Please fill out this field';
  patternError = 'Please enter valid value';

  focus = e => this.setState({ focus: true })
  blur = e => this.setState({ focus: false, mature: true })
  change = e => {
    this.valid();
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  }

  valid() {
    let {
      pattern,
      required,
      validator,
      requiredError,
      patternError
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
      error = validator(defaultValue) || '';
      el.setCustomValidity(error);
    }

    this.setState({ error });
  }

  setRef = el => {
    this.el = el;
    if (el) {
      this.valid();
    }
  }

  render() {
    let allProps = separateDomProps(this.props);
    let InputTag = allProps.tag;

    return (
      <label class={inputClass(this)}>
        <Label text={allProps.label} />
        <div class='Input-content'>
          <InputTag
            {...allProps.props}
            onFocus={this.focus}
            onBlur={this.blur}
            onChange={this.change}
            class='Input-el'
            defaultValue={allProps.defaultValue}
            ref={this.setRef}
          />
          <Error text={this.state.error} />
          <Description text={allProps.description} />
        </div>
      </label>
    )
  }
}

class Check extends Field {
  className = 'Input--checkbox Input-content'

  toggle = e => {
    e.target.value = e.target.checked ? 1 : 0;
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  }

  checked = Boolean(Number(this.props.defaultValue))

  render() {
    let {
      label,
      description,
      props
    } = separateDomProps(this.props);

    return <label class={inputClass(this)}>
      <input
        {...props}
        defaultChecked={this.checked}
        class='Input-el'
        type='checkbox'
        onChange={this.toggle}
      />
      <div className='Input-checkbox' />
      <Label class='Input-inlineLabel' text={label} />
      <Description description={description} />
    </label>
  }
}

class Radio extends Field {
  className = 'Input--radio'
  state = {
    value: this.props.defaultValue || 0
  }

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
      )
    }
    this.setState({
      value: target.value
    })
    if (this.props.onChange) {
      this.props.onChange(e);
    }
  }

  render() {
    let {
      label,
      description,
      options,
      props
    } = separateDomProps(this.props);

    let defaultValue = this.state.value;
    let selectedDescription;

    return <div class={inputClass(this)}>
      <Label text={label} />
      <div class='Input-content'>
        <div class='Input--radioLabels'>
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

            return <label key={i}>
              <input type='radio'
                {...props}
                defaultChecked={selected}
                onChange={this.toggle}
                value={value}
                class='Input-el'
              />
              <div className='Input-radio' />
              <Label text={label} class='Input-inlineLabel' />
          </label>})}
        </div>
        <Description text={selectedDescription} />
        <Description text={description} />
      </div>
    </div>
  }
}

Field.Radio = Radio;
Field.Check = Check;

Field.Textarea = _ => <Field {..._} tag="textarea" />;
Field.File = _ => <Field {..._} type="file" />;
Field.Time = _ => <Field {..._} type="time" />;

Field.Select = ({options, ...props}) => (
  <Field
    {...props}
    tag="select">
    {options.map((o, i) => {
      if (Array.isArray(o)) {
        return <option key={i} value={i}>{o}</option>
      }
      return <option key={o.value} value={o.value}>{o.label}</option>
    })}
  </Field>
)

Field.Group = ({children}) => <div class='InputGroup'>{children}</div>
