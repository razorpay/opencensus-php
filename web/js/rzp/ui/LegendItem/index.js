import React, { Component } from 'react';

import LegendLabel from './LegendLabel';
import LegendTitle from './LegendTitle';
import LegendContent from './LegendContent';

import { checkChildrenType } from 'rzp/utils/rzp-react-utils';

class LegendItemInner extends Component {
  render() {
    const { children, ...itemProps } = this.props;

    itemProps.className = `rzp-legend-item-inner`;

    return <div {...itemProps}>{children}</div>;
  }
}

LegendItemInner.propTypes = {
  children: props => {
    const { children } = props;

    return checkChildrenType(children, [
      LegendLabel,
      LegendTitle,
      LegendContent,
    ]);
  },
};

class LegendItem extends Component {
  render() {
    const { children, className = '', ...otherProps } = this.props;

    otherProps.className = `rzp-legend-item${className ? ' ' : ''}${className}`;

    return (
      <div {...otherProps}>
        <LegendItemInner>{children}</LegendItemInner>
      </div>
    );
  }
}

export { LegendLabel, LegendTitle, LegendContent };

export default LegendItem;
