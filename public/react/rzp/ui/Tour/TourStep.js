import { Component } from 'react';

export default class TourStep extends Component {
  render() {
    return (
      <div class={`TourStep ${this.props.className}`}>
        {this.props.children}
      </div>
    );
  }
}
