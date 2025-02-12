import { Component } from 'react';
import { findDOMNode } from 'react-dom';
import { Field } from 'redux-form';

export default class Clipboard extends Component {
  constructor() {
    super(...arguments);
    this.state = {};
    this.copyToClipboard = this.copyToClipboard.bind(this);
    this.selectValue = this.selectValue.bind(this);
  }

  UNSAFE_componentWillMount() {
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

    this.props.onCopyToClipboard && this.props.onCopyToClipboard();
  }

  render() {
    return (
      <div
        className={`input-group Clipboard ${
          this.supported ? '' : 'Clipboard--unsupported'
        }`}
      >
        <input
          value={this.props.value}
          className="form-control Clipboard__input"
          readOnly={true}
          ref={input => (this.input = input)}
          onFocus={this.selectValue}
        />
        {this.supported && (
          <span
            className="input-group-addon"
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
