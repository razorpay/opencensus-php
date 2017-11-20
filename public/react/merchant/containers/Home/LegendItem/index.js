import React from 'react';

import LegendLabel from './LegendLabel';
import LegendTitle from './LegendTitle';
import LegendContent from './LegendContent';

class LegendItem extends Component {
  render() {
    const { children, className, ...itemProps } = this.props,
      orderedChildren = [];

    itemProps.className = `rzp-legend-item ${className}`;

    Reach.Children.forEach(children, child => {
      switch (child.type) {
        case LegendLabel:
          orderedChildren.splice(0, 0, child);
          break;

        case LegendTitle:
          orderedChildren.splice(1, 0, child);
          break;

        case LegendContent:
          orderedChildren.splice(2, 0, child);
          break;
      }
    });

    return (
      <div {...itemProps}>
        {orderedChildren.map((child, index) => {
          return <div key={index}>{child}</div>;
        })}
      </div>
    );
  }
}

LegendItem.propTypes = {
  children: props => {
    const { children } = props;

    let error = null;

    React.Children.forEach(children, child => {
      if (error) {
        return error;
      }

      if (
        !child ||
        child.type !== LegendLabel ||
        child.type !== LegendTitle ||
        child.type !== LegendContent
      ) {
        error =
          `Children should be one of "LegendLabel" or "LegendTitle"` +
          ` or "LegendContent"`;
      }
    });

    return error;
  },
};

export default LegendItem;
