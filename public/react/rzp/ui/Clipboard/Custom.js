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
    if (this.textarea.select) {
      this.textarea.select();
    }
  }

  copyToClipboard() {
    this.selectValue();
    document.execCommand('copy');
  }

  render() {
    if (!this.supported) {
      return null;
    }

    return (
      <div>
        <textarea
          value={this.props.value}
          class="Clipboard__CustomInput"
          readOnly={true}
          ref={textarea => (this.textarea = textarea)}
          onFocus={this.selectValue}
        />
        <div
          onClick={this.copyToClipboard}
          data-tip="Copied"
          data-event="active"
        >
          {this.props.children}
        </div>
      </div>
    );
  }
}
