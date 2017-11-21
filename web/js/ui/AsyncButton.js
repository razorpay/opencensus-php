import React, { Component } from 'react';
import { serialize } from 'ui/Form';
import { confirm } from 'common/modal';

export default class AsyncButton extends Component {
  state = {
    pending: false,
  };

  onClick = ::this.onClick;

  onClick(e) {
    if (this.props.confirm) {
      confirm(this.props.confirm).then(_ => this.processClick(e));
    } else {
      this.processClick(e);
    }
  }

  processClick(e) {
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
    let className = this.state.pending
      ? this.props.pendingClass
      : this.props.className;

    if (this.props.disabled) {
      className += ' disabled';
    }

    return (
      <div class={className} onClick={this.onClick}>
        {this.props.text || this.props.children}
      </div>
    );
  }
}
