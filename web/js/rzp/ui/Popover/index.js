/*
 * TODO( pending things ):
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

    this.state = {
      leftAdjustment: 0, // arrow adjustment
      topAdjustment: 0,
      resultantAlignment: props.align,
    };

    this.onAdjustment = this.onAdjustment.bind(this);
    this.onAlignmentChange = this.onAlignmentChange.bind(this);
  }

  onAlignmentChange(resultantAlignment) {
    this.setState({
      resultantAlignment,
    });
  }

  onAdjustment(leftAdjustment, topAdjustment) {
    this.setState({
      leftAdjustment,
      topAdjustment,
    });
  }

  render() {
    const { children, ...otherProps } = this.props,
      { leftAdjustment, topAdjustment, resultantAlignment } = this.state;

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
      (resultantAlignment || otherProps.align);

    return (
      <Tooltip
        onAdjustment={this.onAdjustment}
        onAlignmentChange={this.onAlignmentChange}
        {...otherProps}
      >
        <div
          className="rzp-popover-arrow"
          style={{
            marginLeft: `${-leftAdjustment}px`,
            marginTop: `${-topAdjustment}px`,
          }}
        />
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
