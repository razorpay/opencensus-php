/*
 * TODO( pending things ):
 * Handle Bottom Position
 */

import React, { Component } from 'react';
import PropTypes from 'prop-types';

const gutter = 10,
  TOOLTIP_DELAY = 200;

class Tooltip extends Component {
  constructor(props) {
    super(props);

    this.state = {
      show: false,
    };

    this.showTooltipTimer = null;
    this.eventsBounded = false;

    this.showTooltip = this.showTooltip.bind(this);
    this.handleMouseEnter = this.handleMouseEnter.bind(this);
    this.handleMouseLeave = this.handleMouseLeave.bind(this);
    this.handleMouseMove = this.handleMouseMove.bind(this);
  }

  showTooltip(data = {}) {
    let { screenX, screenY } = data;

    const node = this.node,
      parent = node.parentElement,
      align = data.align || this.props.align;

    let width = 0,
      height = 0,
      left = 0,
      top = 0;

    const boundingRect = parent.getBoundingClientRect();

    if (!this.props.followPointer) {
      width = boundingRect.width;
      height = boundingRect.height;
      left = boundingRect.left;
      top = boundingRect.top;
    } else {
      left = screenX;
      top = screenY;
    }

    let tooltipWidth =
        this.nodeWidth || (this.nodeWidth = this.node.clientWidth),
      tooltipHeight =
        this.nodeHeight || (this.nodeHeight = this.node.clientHeight),
      screenTop = 0,
      screenLeft = 0,
      screenRight = window.innerWidth,
      screenBottom = window.innerHeight;

    let tooltipLeft = 0,
      tooltipTop = 0,
      paddingTop = 0,
      paddingLeft = 0,
      paddingBottom = 0,
      paddingRight = 0;

    if (align === 'bottom' || align === 'top') {
      tooltipLeft = left + width / 2 - tooltipWidth / 2;

      tooltipTop = top;

      if (align === 'bottom') {
        tooltipTop += height;
        paddingTop = gutter;
      } else {
        tooltipTop -= tooltipHeight;
        tooltipTop -= gutter;
        paddingBottom = gutter;
      }
    } else if (align === 'right' || align === 'left') {
      tooltipLeft = left;

      tooltipTop = top + height / 2 - tooltipHeight / 2;

      if (align === 'right') {
        tooltipLeft += width;
        paddingLeft = gutter;
      } else {
        tooltipLeft -= tooltipWidth;
        paddingRight = gutter;
      }
    }

    let horizontalAdjustment = 0,
      verticalAdjustment = 0;

    if (tooltipLeft < screenLeft) {
      horizontalAdjustment = screenLeft - tooltipLeft;
    } else if (tooltipLeft + tooltipWidth > screenRight) {
      horizontalAdjustment = -(tooltipLeft + tooltipWidth - screenRight);
    }

    if (tooltipTop < screenTop) {
      verticalAdjustment = screenTop - tooltipTop;
    } else if (tooltipTop + tooltipHeight > screenBottom) {
      verticalAdjustment = -(tooltipTop + tooltipHeight - screenBottom);
    }

    let adjustedAlignment = null;

    if (horizontalAdjustment || verticalAdjustment) {
      if (align === 'left' || align === 'right') {
        tooltipTop += verticalAdjustment;

        if (horizontalAdjustment) {
          const isLeftAdjustment = horizontalAdjustment > 0;

          if (
            isLeftAdjustment &&
            horizontalAdjustment + tooltipLeft + tooltipWidth <= screenRight
          ) {
            return this.changeAlignment('right');
          }

          if (
            !isLeftAdjustment &&
            tooltipLeft + horizontalAdjustment >= screenLeft
          ) {
            return this.changeAlignment('left');
          }
        }
      } else if (align === 'top' || align === 'bottom') {
        tooltipLeft += horizontalAdjustment;

        if (verticalAdjustment) {
          const isTopAdjustment = verticalAdjustment > 0;

          if (
            isTopAdjustment &&
            verticalAdjustment + tooltipTop + tooltipHeight <= screenBottom
          ) {
            return this.changeAlignment('bottom');
          }

          if (
            !isTopAdjustment &&
            verticalAdjustment + tooltipTop >= screenTop
          ) {
            return this.changeAlignment('top');
          }
        }
      }

      if (this.props.onAdjustment) {
        this.props.onAdjustment(horizontalAdjustment, verticalAdjustment);
      }
    }

    if (!this.props.followPointer) {
      node.style.top = tooltipTop + 'px';
      node.style.left = tooltipLeft + 'px';
      node.style.paddingLeft = paddingLeft + 'px';
      node.style.paddingTop = paddingTop + 'px';
      node.style.paddingBottom = paddingBottom + 'px';
      node.style.paddingRight = paddingRight + 'px';
    } else {
      node.style.top = tooltipTop + paddingTop - paddingBottom + 'px';
      node.style.left = tooltipLeft + paddingLeft - paddingRight + 'px';
    }

    this.setState({
      show: true,
    });
  }

  changeAlignment(resultantAlignment) {
    this.showTooltip({ align: resultantAlignment });

    return (
      this.props.onAlignmentChange &&
      this.props.onAlignmentChange(resultantAlignment)
    );
  }

  hideTooltip() {
    this.setState({
      show: false,
    });

    // reset alignment adjustment
    return this.props.onAlignmentChange && this.props.onAlignmentChange();
  }

  onShowTooltip() {
    this.showTooltipTimer = window.setTimeout(
      this.showTooltip,
      this.props.delay
    );
  }

  onHideTooltip() {
    window.clearTimeout(this.showTooltipTimer);
  }

  handleMouseMove(e) {
    this.onHideTooltip();
    this.hideTooltip();

    this.showTooltipTimer = window.setTimeout(() => {
      this.showTooltip({
        screenX: e.clientX,
        screenY: e.clientY,
      });
    }, this.props.delay);
  }

  handleMouseEnter(e) {
    this.onShowTooltip();
  }

  handleMouseLeave() {
    this.onHideTooltip();
    this.hideTooltip();
  }

  bindEvents() {
    const { followPointer } = this.props,
      parent = this.node.parentElement;

    if (!followPointer) {
      parent.addEventListener('mouseenter', this.handleMouseEnter);
    } else {
      parent.addEventListener('mousemove', this.handleMouseMove);
    }
    parent.addEventListener('mouseleave', this.handleMouseLeave);
    window.addEventListener('scroll', this.handleMouseLeave);

    this.eventsBounded = true;
  }

  unbindEvents() {
    if (!this.eventsBounded) {
      return;
    }

    const { followPointer } = this.props,
      parent = this.node.parentElement;

    if (!followPointer) {
      parent.removeEventListener('mouseenter', this.handleMouseEnter);
    } else {
      parent.removeEventListener('mousemove', this.handleMouseMove);
    }
    parent.removeEventListener('mouseleave', this.handleMouseLeave);
    window.removeEventListener('scroll', this.handleMouseLeave);

    this.eventsBounded = false;
  }

  componentDidMount() {
    if (this.props.persistent) {
      this.showTooltip();
      return;
    }

    this.bindEvents();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.align !== nextProps.align) {
      this.showTooltip({ align: nextProps.align });
    }
  }

  componentWillUnmount() {
    this.unbindEvents();
  }

  render() {
    const { show } = this.state,
      {
        children,
        align,
        delay,
        followPointer,
        persistent,
        onAdjustment,
        onAlignmentChange,
        ...otherProps
      } = this.props;

    otherProps.className = `${
      otherProps.className ? otherProps.className + ' ' : ''
    }rzp-tooltip${show ? ' show' : ''}`;

    return (
      <div {...otherProps} ref={node => (this.node = node)}>
        <div className="rzp-tooltip-inner">{children}</div>
      </div>
    );
  }
}

Tooltip.defaultProps = {
  align: 'bottom',
  delay: TOOLTIP_DELAY,
  followPointer: false,
  persistent: false,
};

Tooltip.propTypes = {
  align: PropTypes.oneOf(['top', 'bottom', 'left', 'right']),
  followPointer: PropTypes.bool.isRequired,
  persistent: PropTypes.bool.isRequired,
  delay: PropTypes.number.isRequired,
  onAdjustment: PropTypes.func,
  onAlignmentChange: PropTypes.func,
};

export default Tooltip;
