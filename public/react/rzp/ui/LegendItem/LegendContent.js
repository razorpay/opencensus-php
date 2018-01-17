import React, { Component } from 'react';

class LegendContent extends Component {
  render() {
    return <div className="rzp-legend-content">{this.props.children}</div>;
  }
}

export default LegendContent;
