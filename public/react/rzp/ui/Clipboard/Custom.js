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
    this.props.onCopy && this.props.onCopy(this.props.value);
  }

  render() {
    if (!this.supported) {
      return null;
    }

    return (
      <div class="ClipboardCustom">
        <textarea
          value={this.props.value}
          class="ClipboardCustom__Input"
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
