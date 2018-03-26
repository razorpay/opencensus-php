/*
 * TODO( pending things ):
 * Popover caret Position
 * Popover right position
 * Multiple trigger events, click, hover ... etc
 */

import React, { Component } from 'react';

import { isChildSameType, checkChildrenType } from 'rzp/utils/rzp-react-utils';
import Tooltip from 'rzp/ui/Tooltip';

class PopoverTitle extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, ...otherProps } = this.props;

    otherProps.className =
      (otherProps.className ? otherProps.className + ' ' : '') +
      'rzp-popover-title';

    return <div {...otherProps}>{children}</div>;
  }
}

class PopoverBody extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, ...otherProps } = this.props;

    otherProps.className =
      (otherProps.className ? otherProps.className + ' ' : '') +
      'rzp-popover-body';

    return <div {...otherProps}>{children}</div>;
  }
}

class Popover extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, ...otherProps } = this.props;

    let popoverTitle = null,
      popoverBody = null;

    React.Children.forEach(children, child => {
      if (!popoverTitle && isChildSameType(child, PopoverTitle)) {
        popoverTitle = child;
      }

      if (!popoverBody && isChildSameType(child, PopoverBody)) {
        popoverBody = child;
      }
    });

    otherProps.className =
      (otherProps.className ? otherProps.className + ' ' : '') +
      'rzp-popover ' +
      ' align-' +
      otherProps.align;

    return (
      <Tooltip {...otherProps}>
        <div className="rzp-popover-content">
          {popoverTitle && (
            <popoverTitle.type {...popoverTitle.props}>
              {popoverTitle.props.children}
            </popoverTitle.type>
          )}
          {popoverBody && (
            <popoverBody.type {...popoverBody.props}>
              {popoverBody.props.children}
            </popoverBody.type>
          )}
        </div>
      </Tooltip>
    );
  }
}

Popover.propTypes = {
  children: ({ children }) =>
    checkChildrenType(children, [PopoverTitle, PopoverBody]),
  ...Tooltip.propTypes,
};

Popover.defaultProps = {
  ...Tooltip.defaultProps,
};

export { PopoverTitle, PopoverBody, Popover };

export default Popover;
