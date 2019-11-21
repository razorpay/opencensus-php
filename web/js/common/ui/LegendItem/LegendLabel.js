import React, { Component } from 'react';
import PropTypes from 'prop-types';

class LegendLabel extends Component {
  render() {
    const { children, className = '', ...labelProps } = this.props;

    labelProps.className = `rzp-legend-label ${className}`;
    labelProps.style = {
      ...labelProps.style,
      backgroundColor: this.props.color,
    };

    return <div {...labelProps}>{children}</div>;
  }
}

LegendLabel.propTypes = {
  children: PropTypes.node,
  color: PropTypes.string,
};

LegendLabel.defaultProps = {
  color: '#4B4F66',
};

export default LegendLabel;
