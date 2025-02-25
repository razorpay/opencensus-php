import React, { Component } from 'react';
import { connect } from 'react-redux';
import { dispatchWebViewEvent } from '@libs/shared-utils';
import { compose } from 'redux';

class Clipboard extends Component {
  constructor(props) {
    super(props);
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
    if (this.textarea.select) {
      this.textarea.select();
    }
  }

  copyToClipboard(e) {
    e.stopPropagation();
    if (this.props?.isWebView) {
      dispatchWebViewEvent({
        eventType: 'COPY',
        data: this.props.value,
      });
    } else {
      this.selectValue();
      document.execCommand('copy');
    }

    this.props.onCopy && this.props.onCopy(this.props.value);
  }

  render() {
    if (!this.supported) {
      return null;
    }

    const tooltipProps = {
      'data-event': 'active',
    };

    if (!this.props.hideTooltip) {
      tooltipProps['data-tip'] = 'Copied';
    }

    return (
      <div className="ClipboardCustom">
        <textarea
          value={this.props.value}
          className="ClipboardCustom__Input"
          readOnly={true}
          ref={(textarea) => (this.textarea = textarea)}
          onFocus={this.selectValue}
        />
        <div onClick={this.copyToClipboard} {...tooltipProps}>
          {this.props.children}
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      isWebView: state.app?.isWebView,
    }),
    null,
  ),
)(Clipboard);
