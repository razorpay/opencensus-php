import React, { Component } from 'react';
import Hc from 'highcharts';
import deepMerge from 'deepmerge';

import defaults from './defaults.js';

class Highcharts extends Component {
  componentDidMount() {
    Hc.chart(this.container, deepMerge(defaults, this.props.options || {}));
  }

  render() {
    return (
      <div className="hc-container" ref={node => (this.container = node)} />
    );
  }
}

export default Highcharts;
