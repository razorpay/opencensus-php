import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';
import { createLineData } from 'util/chart';

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
        <Line data={createLineData(value)} />
      </div>
    );
  }
}
