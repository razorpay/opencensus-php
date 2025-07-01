import React from "react";
import debounce from 'common/utils/debounce';
import { classList } from 'common/utils/rzp-utils';

function isJSONString(str) {
  try {
    var json = JSON.parse(str);
    return typeof json === 'object';
  } catch (e) {
    return false;
  }
}

const syntaxErrorMsg = 'JSON Syntax is invalid';

export default class extends React.PureComponent {
  state = { isInValid: null };

  componentDidMount() {
    this.flask = new window.CodeFlask('#json-edit-view', {
      language: 'js',
      defaultTheme: false,
      readonly: !!this.props.isReadOnly,
    });

    const ta = document.getElementsByClassName('codeflask__textarea')[0];

    ta.addEventListener('blur', e => {
      if (this.isValidJSON(this.flask.getCode())) {
        const errorMsg = this.deepValidator();

        errorMsg && this.setState({ isInValid: errorMsg });
      }
    });

    ta.focus();

    this.flask.onUpdate(code => {
      this.onUpdate(code);
    });

    const initialJSON = this.props.initialJSON;
    this.setState({
      jsonValu: initialJSON,
    });

    initialJSON && this.flask.updateCode(JSON.stringify(initialJSON, null, 2));
  }

  deepValidator() {
    const validator = this.props.validatorJSON;
    let isInValid;

    // Do deep check only when blurred
    if (validator) {
      const JSON2Obj = JSON.parse(this.flask.getCode());
      const allValidators = { ...validator.required, ...validator.notRequired };

      for (let i = 0; i < Object.keys(allValidators).length; i++) {
        const k = Object.keys(allValidators)[i],
          valInJSON = JSON2Obj[k];
        let errorMsg;

        if (typeof valInJSON === 'undefined' && !!validator.required[k]) {
          errorMsg = k + ' is missing'; // => If validator is present but valueInJSON is undefined
        } else {
          errorMsg = allValidators[k] && allValidators[k](valInJSON);
        }

        if (errorMsg) {
          isInValid = errorMsg;
          break;
        }
      }

      return isInValid;
    }
  }

  onUpdate(code) {
    let isInValid = !this.isValidJSON(code) ? syntaxErrorMsg : null;

    if (!isInValid && this.state.isInValid) {
      const errorMsg = this.deepValidator();

      if (errorMsg === this.state.isInValid) {
        // Ignoring other errors until blur
        isInValid = errorMsg;
      }
    }

    this.setState({
      jsonValue: code,
    });

    if (isInValid !== this.state.isInValid) {
      this.setState({ isInValid });
    }
  }

  onUpdate = debounce(this.onUpdate, 100);

  isValidJSON(text) {
    if (typeof text !== 'string') {
      return false;
    }

    try {
      JSON.parse(text);
      return true;
    } catch (error) {
      return false;
    }
  }

  render() {
    return (
      <div
        className={classList(
          'JSONEdit-container',
          this.state.isInValid && 'is-invalid'
        )}
      >
        <input
          name="json-value"
          value={this.flask ? this.state.jsonValue : ''}
          className="hide"
          readOnly={this.props.isReadOnly}
        />
        <div id="json-edit-view" />
        <div className={classList('error', !this.state.isInValid && 'hidden')}>
          <i className="i-info-circle" />
          {this.state.isInValid}
        </div>
      </div>
    );
  }
}
