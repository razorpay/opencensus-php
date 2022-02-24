import React, { Component } from 'react';
import Hc from 'highcharts';
import deepMerge from 'deepmerge';

import defaults from './defaults.js';

class Highcharts extends Component {
  componentDidMount() {
    Hc.chart(
      this.container,
      deepMerge(defaults, this.props.options || {}),
      chart => (this.chart = chart)
    );
  }

  UNSAFE_componentWillReceiveProps({ options }) {
    if (this.chart) {
      this.chart.update(options);
    }
  }

  componentWillUnmount() {
    this.chart.destroy();
    this.chart = null;
  }

  render() {
    return (
      <div className="rzp-highcharts" ref={node => (this.container = node)} />
    );
  }
}

export default Highcharts;
