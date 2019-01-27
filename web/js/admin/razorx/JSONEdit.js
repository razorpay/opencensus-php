import debounce from 'rzp/utils/debounce';
import { classList } from 'common/util';

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
    initialJSON && this.flask.updateCode(JSON.stringify(initialJSON, null, 2));
  }

  deepValidator() {
    const validator = this.props.validatorJSON;
    let isInValid;

    // Do deep check only when blurred
    if (validator) {
      const JSON2Obj = JSON.parse(this.flask.getCode());

      for (let i = 0; i < Object.keys(validator).length; i++) {
        const k = Object.keys(validator)[i],
          valInJSON = JSON2Obj[k];
        let errorMsg;

        if (typeof valInJSON === 'undefined' && !!validator[k]) {
          errorMsg = k + ' is missing'; // => If validator is present but valueInJSON is undefined
        } else {
          errorMsg = validator[k] && validator[k](valInJSON);
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
        class={classList(
          'JSONEdit-container',
          this.state.isInValid && 'is-invalid'
        )}
      >
        <input
          name="json-value"
          value={
            !this.state.isInValid && this.flask ? this.flask.getCode() : ''
          }
          class="hide"
        />
        <div id="json-edit-view" />
        <div class={classList('error', !this.state.isInValid && 'hidden')}>
          <i class="i-info-circle" />
          {this.state.isInValid}
        </div>
      </div>
    );
  }
}
