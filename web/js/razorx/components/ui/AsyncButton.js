import React, { Component } from 'react';
import { serialize } from 'razorx/components/ui/Form';
import { confirm } from 'razorx/components/Modal';
import { prevent } from 'common/utils/rzp-utils';

export default class AsyncButton extends Component {
  state = {
    pending: false,
  };

  onClick = this.onClick.bind(this);

  onClick(e) {
    if (this.props.confirm) {
      confirm(this.props.confirm).then(_ => this.processClick(e));
    } else {
      this.processClick(e);
    }

    e.persist(); // e.prevenDefault makes synthetic even to get removed. Synthetic even is needed for performance reasons
    prevent(e);
  }

  processClick(e) {
    if (!this.state.pending) {
      let { onSubmit, onClick } = this.props;

      let formData = {};
      if (onSubmit) {
        let form = e.currentTarget
          ? e.currentTarget.closest('form')
          : e.target.closest('form');
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
      <div className={className} onClick={this.onClick}>
        {this.props.text || this.props.children}
      </div>
    );
  }
}
