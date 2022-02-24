/*
 * TODO( pending things ):
 * Handle Bottom Position
 */

import React, { Component } from 'react';
import PropTypes from 'prop-types';

import debounce from 'common/utils/debounce';

const DEFAULT_OFFSET = 10;
const TOOLTIP_DELAY = 200;

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
    this.handleScroll = debounce(this.handleMouseLeave, 100);
  }

  getDimensions(data) {
    const { screenX, screenY } = data;

    const node = this.node;
    const parent = node.parentElement;
    const align = data.align || this.props.align;
    const offset = this.props.offset;

    let parentWidth = 0;
    let parentHeight = 0;
    let parentLeft = 0;
    let parentTop = 0;

    const boundingRect = parent.getBoundingClientRect();

    if (!this.props.followPointer) {
      parentWidth = boundingRect.width;
      parentHeight = boundingRect.height;
      parentLeft = boundingRect.left;
      parentTop = boundingRect.top;
    } else {
      parentLeft = screenX;
      parentTop = screenY;
    }

    const tooltipWidth = this.nodeWidth || (this.nodeWidth = this.node.clientWidth);
    const tooltipHeight = this.nodeHeight || (this.nodeHeight = this.node.clientHeight);

    let tooltipLeft = 0;
    let tooltipTop = 0;
    let paddingTop = 0;
    let paddingLeft = 0;
    let paddingBottom = 0;
    let paddingRight = 0;

    if (align === 'bottom' || align === 'top') {
      tooltipLeft = parentLeft + parentWidth / 2 - tooltipWidth / 2;

      tooltipTop = parentTop;

      if (align === 'bottom') {
        tooltipTop += parentHeight;
        paddingTop = offset;
      } else {
        tooltipTop -= tooltipHeight;
        paddingBottom = offset;
      }
    } else if (align === 'right' || align === 'left') {
      tooltipLeft = parentLeft;

      tooltipTop = parentTop + parentHeight / 2 - tooltipHeight / 2;

      if (align === 'right') {
        tooltipLeft += parentWidth;
        paddingLeft = offset;
      } else {
        tooltipLeft -= tooltipWidth;
        paddingRight = offset;
      }
    }

    return {
      tooltipTop,
      tooltipLeft,
      tooltipWidth,
      tooltipHeight,
      paddingLeft,
      paddingRight,
      paddingTop,
      paddingBottom,
    };
  }

  showTooltip(data = {}) {
    if (!this.node) return null;
    const node = this.node;
    const align = data.align || this.props.align;

    const screenTop = 0;
    const screenLeft = 0;
    const screenRight = window.innerWidth;
    const screenBottom = window.innerHeight;

    let { tooltipTop, tooltipLeft } = this.getDimensions(data);
    const {
      tooltipWidth,
      tooltipHeight,
      paddingLeft,
      paddingRight,
      paddingTop,
      paddingBottom,
    } = this.getDimensions(data);

    let horizontalAdjustment = 0;
    let verticalAdjustment = 0;
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

    if (horizontalAdjustment || verticalAdjustment) {
      if (align === 'left' || align === 'right') {
        tooltipTop += verticalAdjustment;

        if (horizontalAdjustment) {
          const isLeftAdjustment = horizontalAdjustment > 0;

          const { tooltipLeft: tooltipLeftOnRightAlignment } = this.getDimensions({
            ...data,
            align: 'right',
          });

          if (
            isLeftAdjustment &&
            horizontalAdjustment + tooltipLeftOnRightAlignment + tooltipWidth <= screenRight
          ) {
            return this.changeAlignment(data, 'right');
          }

          const { tooltipLeft: tooltipLeftOnLeftAlignment } = this.getDimensions({
            ...data,
            align: 'left',
          });

          if (
            !isLeftAdjustment &&
            tooltipLeftOnLeftAlignment + horizontalAdjustment >= screenLeft
          ) {
            return this.changeAlignment(data, 'left');
          }
        }
      } else if (align === 'top' || align === 'bottom') {
        tooltipLeft += horizontalAdjustment;

        if (verticalAdjustment) {
          const isTopAdjustment = verticalAdjustment > 0;

          const { tooltipTop: tooltipTopOnBottomAlignment } = this.getDimensions({
            ...data,
            align: 'bottom',
          });

          if (
            isTopAdjustment &&
            verticalAdjustment + tooltipTopOnBottomAlignment + tooltipHeight <= screenBottom
          ) {
            return this.changeAlignment(data, 'bottom');
          }

          const { tooltipTop: tooltipTopOnTopAlignment } = this.getDimensions({
            ...data,
            align: 'top',
          });

          if (!isTopAdjustment && verticalAdjustment + tooltipTopOnTopAlignment >= screenTop) {
            return this.changeAlignment(data, 'top');
          }
        }
      }

      if (this.props.onAdjustment) {
        this.props.onAdjustment(horizontalAdjustment, verticalAdjustment);
      }
    }

    const ele = document.querySelector(this.props.parentQuerySelector);

    const parentAdjustment = { top: 0, left: 0 };
    if (ele && this.props.parentQuerySelector) {
      // Relative height of parent wrt to window
      const viewportOffset = ele.getBoundingClientRect();
      const top = viewportOffset.top;
      const left = viewportOffset.left;

      parentAdjustment.left = left;
      parentAdjustment.top = top;
    }

    if (!this.props.followPointer) {
      // when paddingBottom is present, top needs to be adjusted as
      // the box grows down
      node.style.top = `${tooltipTop - parentAdjustment.top - paddingBottom}px`;
      node.style.left = `${tooltipLeft - parentAdjustment.left}px`;
      node.style.paddingLeft = `${paddingLeft}px`;
      node.style.paddingTop = `${paddingTop}px`;
      node.style.paddingBottom = `${paddingBottom}px`;
      node.style.paddingRight = `${paddingRight}px`;
    } else {
      node.style.top = `${tooltipTop + paddingTop - paddingBottom}px`;
      node.style.left = `${tooltipLeft + paddingLeft - paddingRight}px`;
    }

    this.setState({
      show: true,
    });
    return null;
  }

  changeAlignment(data, resultantAlignment) {
    this.showTooltip({ align: resultantAlignment });
    return this.props.onAlignmentChange && this.props.onAlignmentChange(resultantAlignment);
  }

  hideTooltip() {
    if (!this.state.show) {
      return;
    }

    this.setState({
      show: false,
    });

    // reset alignment adjustment
    if (this.props.onAlignmentChange) this.props.onAlignmentChange();
  }

  onShowTooltip() {
    this.showTooltipTimer = window.setTimeout(this.showTooltip, this.props.delay);
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

  handleMouseEnter(_) {
    this.onShowTooltip();
  }

  handleMouseLeave() {
    this.onHideTooltip();
    this.hideTooltip();
  }

  bindEvents() {
    const { followPointer } = this.props;
    const parent = this.node.parentElement;

    if (!followPointer) {
      parent.addEventListener('mouseenter', this.handleMouseEnter, {
        passive: true,
      });
    } else {
      parent.addEventListener('mousemove', this.handleMouseMove, {
        passive: true,
      });
    }
    parent.addEventListener('mouseleave', this.handleMouseLeave, {
      passive: true,
    });
    window.addEventListener('scroll', this.handleScroll, { passive: true });

    this.eventsBounded = true;
  }

  unbindEvents() {
    if (!this.eventsBounded) {
      return;
    }

    const { followPointer } = this.props;
    const parent = this.node.parentElement;

    if (!followPointer) {
      parent.removeEventListener('mouseenter', this.handleMouseEnter);
    } else {
      parent.removeEventListener('mousemove', this.handleMouseMove);
    }
    parent.removeEventListener('mouseleave', this.handleMouseLeave);
    window.removeEventListener('scroll', this.handleScroll);

    this.eventsBounded = false;
  }

  componentDidMount() {
    if (this.props.persistent) {
      this.showTooltip();
      return;
    }

    this.bindEvents();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.persistent !== this.props.persistent) {
      if (nextProps.persistent) {
        this.showTooltip();
      } else {
        this.hideTooltip();
      }
    }

    if (this.props.align !== nextProps.align) {
      this.showTooltip({ align: nextProps.align });
    }
  }

  componentWillUnmount() {
    this.unbindEvents();
  }

  render() {
    const { show } = this.state;
    const {
      children,
      align,
      delay,
      offset,
      followPointer,
      persistent,
      onAdjustment,
      onAlignmentChange,
      theme,
      parentQuerySelector,
      ...otherProps
    } = this.props;

    otherProps.className = `${otherProps.className ? `${otherProps.className} ` : ''}rzp-tooltip${
      show ? ' show' : ''
    } theme-${theme}`;

    return (
      <div {...otherProps} ref={(node) => (this.node = node)}>
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
  offset: DEFAULT_OFFSET,
  theme: 'light',
};

Tooltip.propTypes = {
  align: PropTypes.oneOf(['top', 'bottom', 'left', 'right']),
  followPointer: PropTypes.bool,
  persistent: PropTypes.bool,
  delay: PropTypes.number,
  offset: PropTypes.number,
  onAdjustment: PropTypes.func,
  onAlignmentChange: PropTypes.func,
  theme: PropTypes.oneOf(['light', 'dark']),
};

export default Tooltip;
