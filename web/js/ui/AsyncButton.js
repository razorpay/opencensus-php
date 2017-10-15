import React, { Component } from 'react';

export default class AsyncButton extends Component {
  state = {
    pending: false,
  };

  onClick = ::this.onClick;

  onClick(e) {
    if (!this.state.pending) {
      let onClickValue = this.props.onClick(e);
      if (onClickValue instanceof Promise) {
        this.setState({
          pending: true,
        });
        onClickValue.then(_ => {
          this.setState({
            pending: false,
          });
        });
      }
    }
  }

  render() {
    var className = this.state.pending
      ? this.props.pendingClass
      : this.props.className;
    return (
      <div class={className} onClick={this.onClick}>
        {this.props.text}
      </div>
    );
  }
}
