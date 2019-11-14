import React, { Component } from 'react';
import PropTypes from 'prop-types';

import { checkChildrenType } from 'common/utils/react-utils';
import LegendItem, {
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'common/ui/LegendItem';

class Legend extends Component {
  render() {
    const { children, className = '', alignment, ...otherProps } = this.props;

    otherProps.className =
      `rzp-legend ${className} ` + `rzp-legend-${alignment}`;

    return <div {...otherProps}>{children}</div>;
  }
}

Legend.propTypes = {
  alignment: PropTypes.oneOf(['horizontal', 'vertical']),
  children: props => {
    const { children } = props;

    return checkChildrenType(children, [LegendItem]);
  },
};

Legend.defaultProps = {
  alignment: 'horizontal',
};

export { LegendItem, LegendLabel, LegendTitle, LegendContent };

export default Legend;
