import { Component } from 'react';
import { findDOMNode } from 'react-dom';
import { Field } from 'redux-form';
import './Clipboard.styl';

export default class Clipboard extends Component {
  constructor() {
    super(...arguments);
    this.state = {};
    this.copyToClipboard = ::this.copyToClipboard;
    this.selectValue = ::this.selectValue;
  }

  componentWillMount() {
    if (document.execCommand) {
      this.supported = true;
    }
  }

  selectValue() {
    if (this.input.select) {
      this.input.select();
    }
  }

  copyToClipboard() {
    this.selectValue();
    document.execCommand('copy');
  }

  render() {
    return (
      <div
        class={`input-group Clipboard ${
          this.supported ? '' : 'Clipboard--unsupported'
        }`}
      >
        <input
          value={this.props.value}
          class="form-control Clipboard__input"
          readOnly={true}
          ref={input => (this.input = input)}
          onFocus={this.selectValue}
        />
        {this.supported && (
          <span
            class="input-group-addon"
            onClick={this.copyToClipboard}
            data-tip="Copied"
            data-event="active"
          >
            Copy Link
          </span>
        )}
      </div>
    );
  }
}
