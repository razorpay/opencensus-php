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

export default class extends React.PureComponent {
  state = { isValid: true };

  componentDidMount() {
    this.flask = new window.CodeFlask('#json-edit-view', {
      language: 'js',
      defaultTheme: false,
    });

    const ta = document.getElementsByClassName('codeflask__textarea')[0];

    ta.focus();

    this.flask.onUpdate(code => {
      this.onUpdate(code);
    });

    //this.flask.getCode();

    const initialJSON = this.props.initialJSON;
    initialJSON && this.flask.updateCode(JSON.stringify(initialJSON, null, 2));
  }

  onUpdate(code) {
    console.log('code....', code);
    const isValid = this.isValidJSON(code);
    if (isValid !== this.state.isValid) {
      this.setState({ isValid });
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
      <div class="JSONEdit-container">
        <div id="json-edit-view" />
        <div class={classList('error', this.state.isValid && 'hidden')}>
          <i class="i-info-circle" />JSON is invalid!
        </div>
      </div>
    );
  }
}
