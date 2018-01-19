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

    this.showTooltip = this.showTooltip.bind(this);
    this.handleMouseEnter = this.handleMouseEnter.bind(this);
    this.handleMouseLeave = this.handleMouseLeave.bind(this);
    this.handleMouseMove = this.handleMouseMove.bind(this);
  }

  showTooltip(data = {}) {
    let { screenX, screenY } = data;

    const node = this.node,
      parent = node.parentElement,
      { align } = this.props;

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

    const {
        width: tooltipWidth,
        height: tooltipHeight,
      } = node.getBoundingClientRect(),
      screenLeft = 0,
      screenRight = document.body.clientWidth;

    let tooltipLeft = 0,
      tooltipTop = 0,
      paddingTop = 0,
      paddingLeft = 0;

    // TODO: need to handle other alignment options also
    if (align === 'bottom') {
      tooltipLeft = left + width / 2 - tooltipWidth / 2;
      tooltipTop = top;

      if (!this.props.followPointer) {
        tooltipTop += height;
      }

      paddingTop = gutter;
    } else if (align === 'right') {
      tooltipLeft = left;

      if (!this.props.followPointer) {
        tooltipLeft += width;
      }

      tooltipTop = top + height / 2 - tooltipHeight / 2;
      paddingLeft = gutter;
    }

    if (tooltipLeft < screenLeft) {
      tooltipLeft += screenLeft - tooltipLeft;
    } else if (tooltipLeft + tooltipWidth > screenRight) {
      tooltipLeft -= tooltipLeft + tooltipWidth - screenRight;
    }

    if (!this.props.followPointer) {
      node.style.top = tooltipTop + 'px';
      node.style.left = tooltipLeft + 'px';
      node.style.paddingLeft = paddingLeft + 'px';
      node.style.paddingTop = paddingTop + 'px';
    } else {
      node.style.top = tooltipTop + paddingTop + 'px';
      node.style.left = tooltipLeft + paddingLeft + 'px';
    }

    this.setState({
      show: true,
    });
  }

  hideTooltip() {
    this.setState({
      show: false,
    });
  }

  onShowTooltip() {
    this.showTooltipTimer = window.setTimeout(this.showTooltip, TOOLTIP_DELAY);
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
    }, TOOLTIP_DELAY);
  }

  handleMouseEnter(e) {
    this.onShowTooltip();
  }

  handleMouseLeave() {
    this.onHideTooltip();
    this.hideTooltip();
  }

  componentDidMount() {
    const { followPointer } = this.props,
      parent = this.node.parentElement;

    if (!followPointer) {
      parent.addEventListener('mouseenter', this.handleMouseEnter);
    } else {
      parent.addEventListener('mousemove', this.handleMouseMove);
    }
    parent.addEventListener('mouseleave', this.handleMouseLeave);
    window.addEventListener('scroll', this.handleMouseLeave);
  }

  componentWillUnmount() {
    const { followPointer } = this.props,
      parent = this.node.parentElement;

    if (!followPointer) {
      parent.removeEventListener('mouseenter', this.handleMouseEnter);
    } else {
      parent.removeEventListener('mousemove', this.handleMouseMove);
    }
    parent.removeEventListener('mouseleave', this.handleMouseLeave);
    window.removeEventListener('scroll', this.handleMouseLeave);
  }

  render() {
    const { show } = this.state;

    return (
      <div
        className={`rzp-tooltip ${show ? 'show' : ''}`}
        ref={node => (this.node = node)}
      >
        <div className="rzp-tooltip-inner">{this.props.children}</div>
      </div>
    );
  }
}

Tooltip.defaultProps = {
  align: 'bottom',
  followPointer: false,
};

Tooltip.propTypes = {
  align: PropTypes.oneOf(['top', 'bottom', 'left', 'right']),
  followPointer: PropTypes.bool.isRequired,
};

export default Tooltip;
