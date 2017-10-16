import React, { Component } from 'react';
import { serialize } from 'ui/Form';

export default class AsyncButton extends Component {
  state = {
    pending: false,
  };

  onClick = ::this.onClick;

  onClick(e) {
    if (!this.state.pending) {
      let { onSubmit, onClick } = this.props;

      let formData = {};
      if (onSubmit) {
        let form = e.currentTarget.closest('form');
        if (form) {
          formData = serialize(form);
        }
      }

      let returnValue = onSubmit ? onSubmit(formData) : onClick(e);

      if (returnValue instanceof Promise) {
        this.setState({
          pending: true,
        });
        returnValue.then(_ => {
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
