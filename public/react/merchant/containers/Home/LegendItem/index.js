import React, { Component } from 'react';

import LegendLabel from './LegendLabel';
import LegendTitle from './LegendTitle';
import LegendContent from './LegendContent';

import { checkChildrenType } from 'rzp/utils/rzp-react-utils';

import './styles.styl';

class LegendItem extends Component {
  render() {
    const { children, className = '', ...itemProps } = this.props;

    itemProps.className = `rzp-legend-item ${className}`;

    return <div {...itemProps}>{children}</div>;
  }
}

LegendItem.propTypes = {
  children: props => {
    const { children } = props;

    return checkChildrenType(children, [
      LegendLabel,
      LegendTitle,
      LegendContent,
    ]);
  },
};

export { LegendLabel, LegendTitle, LegendContent };

export default LegendItem;
