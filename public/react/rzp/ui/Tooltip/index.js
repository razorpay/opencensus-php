import React, { Component } from 'react';
import PropTypes from 'prop-types';

import './styles.styl';

const gutter = 10;

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
  }

  showTooltip() {
    const node = this.node,
      parent = node.parentElement,
      { align } = this.props;

    const { width, height, left, top } = parent.getBoundingClientRect(),
      {
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
      tooltipTop = top + height;
      paddingTop = gutter;
    } else if (align === 'right') {
      tooltipLeft = left + width;
      tooltipTop = top + height / 2 - tooltipHeight / 2;
      paddingLeft = gutter;
    }

    if (tooltipLeft < screenLeft) {
      tooltipLeft += screenLeft - tooltipLeft;
    } else if (tooltipLeft + tooltipWidth > screenRight) {
      tooltipLeft -= tooltipLeft + tooltipWidth - screenRight;
    }

    node.style.top = tooltipTop + 'px';
    node.style.left = tooltipLeft + 'px';
    node.style.paddingLeft = paddingLeft + 'px';
    node.style.paddingTop = paddingTop + 'px';

    this.setState({
      show: true,
    });
  }

  hideTooltip() {
    this.setState({
      show: false,
    });
  }

  handleMouseEnter(e) {
    this.showTooltipTimer = window.setTimeout(this.showTooltip, 500);
  }

  handleMouseLeave() {
    window.clearTimeout(this.showTooltipTimer);

    this.hideTooltip();
  }

  componentDidMount() {
    const parent = this.node.parentElement;

    parent.addEventListener('mouseenter', this.handleMouseEnter);
    parent.addEventListener('mouseleave', this.handleMouseLeave);
    window.addEventListener('scroll', this.handleMouseLeave);
  }

  componentWillUnmount() {
    const parent = this.node.parentElement;

    parent.removeEventListener('mouseenter', this.handleMouseEnter);
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
};

Tooltip.propTypes = {
  align: PropTypes.oneOf(['top', 'bottom', 'left', 'right']),
};

export default Tooltip;
