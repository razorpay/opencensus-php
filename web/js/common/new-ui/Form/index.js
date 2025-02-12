export default class Form extends React.PureComponent {
  state = {
    pending: false,
    mature: false,
  };

  formClass = _ => {
    let className = 'Form';
    if (this.props.layout === 'tabular') {
      className += ' Form--tabular';
    }
    if (this.state.pending) {
      className += ' is-pending';
    }
    if (this.state.mature) {
      className += ' is-mature';
    }
    if (this.props.className) {
      className += ' ' + this.props.className;
    }
    return className;
  };

  render() {
    let {
      beforeSubmit,
      onSubmit,
      layout,
      className,
      setRef,
      ...rest
    } = this.props;

    return (
      <form
        ref={setRef}
        noValidate
        {...rest}
        className={this.formClass()}
        onSubmit={this.onSubmit}
      />
    );
  }

  onSubmit = this.onSubmit.bind(this);
  onSubmit(e) {
    e.preventDefault();
    let { beforeSubmit, validator } = this.props;

    let form = e.target;
    let data = this.serialize(form);

    if (validator) {
      if (validator(data, form)) {
        return this.setState({
          mature: true,
        });
      }
    }

    if (this.state.pending) return;

    // if a before submit hook is provided, invoke it
    if (beforeSubmit) {
      let beforePromise = beforeSubmit.call(this, data);

      // if before submit hook returns a promise, submit on resolution
      if (beforePromise instanceof Promise) {
        return beforePromise.then(_ => this.submit(data));
      }
    }
    return this.submit(data);
  }

  submit(data) {
    let returnPromise = this.props.onSubmit && this.props.onSubmit(data);
    if (returnPromise instanceof Promise) {
      this.setState({
        pending: true,
      });
      returnPromise.catch().then(_ => {
        this.setState({
          pending: false,
        });
      });
    }
    return returnPromise;
  }

  serialize(form) {
    let data = {};
    let lastData;
    Array.prototype.forEach.call(
      form.querySelectorAll('[name]'),
      ({ name, value, type, checked }) => {
        if (type === 'checkbox') {
          data[name] = checked;
        } else if (type === 'radio') {
          if (checked) {
            data[name] = value;
          }
        } else {
          data[name] = value;
        }
      }
    );
    return data;
  }
}

function set(data, name, value) {
  let nestedMatch = name.match(/(\w+)?\[(\w+)]/);
  if (nestedMatch) {
    [matchedPart, superKey, subKey] = nestedMatch;

    // if superKey is undefined, name was `[subKey]`
    if (superKey) {
      if (!data[superKey]) {
        data[superKey] = {};
      }
      return set(
        data[superKey],
        subKey + name.slice(matchedPart.length),
        value
      );
    }
    name = subKey;
  }
  data[name] = value;
}
