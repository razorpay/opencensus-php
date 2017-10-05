import React, { Component } from 'react';

export class Slider extends Component {
  render() {
    return <div class="slider">{this.props.component}</div>;
  }
}

export class Modal extends Component {
  render() {
    return <div class="modal">{this.props.component}</div>;
  }
}

export class Toast extends Component {
  render() {
    return <div class="toast">{this.props.component}</div>;
  }
}
