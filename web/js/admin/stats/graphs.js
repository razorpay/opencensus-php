import React, { Component } from 'react';
import Chartist from 'chartist';

export class Single extends Component {
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

export class Chart extends Component {
  render() {
    let { title, data, options, type } = this.props;
    return (
      <div>
        <header>{title}</header>
        <div ref={el => el && data && makeChart(el, data, options, type)} />
      </div>
    );
  }
}

function makeChart(el, data, options, type) {
  switch (type) {
    case 'bar':
      return new Chartist.Bar(el, data, options);
    case 'line':
      return new Chartist.Line(el, data, options);
    default:
      return null;
  }
}
