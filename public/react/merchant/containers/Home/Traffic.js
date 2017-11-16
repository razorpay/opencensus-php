import React, { Component } from 'react';

import Highcharts from 'rzp/ui/Highcharts';

class Traffic extends Component {
  render() {
    return (
      <div className="panel">
        <div className="clearfix">
          <div className="pull-right">...</div>
          <div className="pull-right">
            <select>
              <option>By Transaction Volume</option>
              <option>By Issuer</option>
            </select>
          </div>
        </div>
        <Highcharts />
      </div>
    );
  }
}

export default Traffic;
