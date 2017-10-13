import React, { Component } from 'react';

export class SingleValue extends Component {
  render() {
    let { title, value } = this.props;

    return (
      <div>
        <header>{title}</header>
        {value}
      </div>
    );
  }
}

export class TimeSeries extends Component {
  render() {
    let { title, value } = this.props;

    return (
      <div>
        <header>{title}</header>
      </div>
    );
  }
}
