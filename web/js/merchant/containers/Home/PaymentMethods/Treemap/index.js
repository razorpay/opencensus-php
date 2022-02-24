import React, { Component } from 'react';
import { connect } from 'react-redux';

import Tooltip from 'common/ui/Tooltip';
import { getFormattedNumber, getFormattedAmountNew } from 'common/utils/rzp-utils';
import { globalGroupTitleMap as groupTitleMap } from 'common/utils/pokedex';

import { bankNames } from '../data';
import renderTreemap from './renderTreemap';

let timer = null;

@connect(null, null)
export default class Treemap extends Component {
  constructor(props) {
    super(props);

    this.state = {
      tooltip: {
        show: false,
        data: {
          amount: 0,
          percent: 0,
          label: '',
        },
      },
    };

    this.treemapApi = null;
    this.componentMounted = false;

    this.onTransition = ::this.onTransition;
    this.handleResize = ::this.handleResize;
    this.onShowTooltip = ::this.onShowTooltip;
    this.onHideTooltip = ::this.onHideTooltip;
  }

  showTooltip({ amount, percent, label, canBeZoomed }) {
    this.setState({
      tooltip: {
        show: true,
        data: {
          amount,
          percent,
          label,
        },
        canBeZoomed,
      },
    });
  }

  hideTooltip() {
    this.setState({
      tooltip: {
        show: false,
      },
    });
  }

  onShowTooltip({ amount, percent, label, canBeZoomed }) {
    this.showTooltip({
      amount,
      percent,
      label,
      canBeZoomed,
    });
  }

  onHideTooltip() {
    this.hideTooltip();
  }

  onTransition(d, isNewData) {
    const { onLevelChange } = this.props;

    this.isNewData = isNewData;
    return typeof onLevelChange === 'function' && onLevelChange(d);
  }

  renderTreemap(data, isCurrency) {
    this.treemapApi = renderTreemap(
      this.node,
      data,
      isCurrency,
      d3,
      this.onTransition,
      this.onShowTooltip,
      this.onHideTooltip,
      groupTitleMap,
      bankNames
    );

    return typeof this.props.onCSVData === 'function' && this.props.onCSVData(this.treemapApi.csv);
  }

  handleResize() {
    window.clearTimeout(timer);

    const parent = this.node.parentNode;

    this.node.style.width = parent.clientWidth + 'px';

    timer = window.setTimeout(() => {
      this.renderTreemap(this.props.data, this.props.isCurrency);
    }, 250);
  }

  componentDidMount() {
    this.componentMounted = true;

    window.addEventListener('resize', this.handleResize);

    return this.props.data && this.renderTreemap(this.props.data, this.props.isCurrency);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { data, currentLevel } = this.props;

    if (data !== nextProps.data) {
      return this.renderTreemap(nextProps.data, nextProps.isCurrency);
    } else if (!this.isNewData && currentLevel && nextProps.currentLevel !== currentLevel) {
      this.treemapApi.transition(nextProps.currentLevel);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  render() {
    const { tooltip } = this.state,
      { isCurrency } = this.props,
      amount = (isCurrency ? getFormattedAmountNew : getFormattedNumber)(tooltip.data.amount, true);

    return (
      <div>
        <div id="payment-methods-treemap" ref={(node) => (this.node = node)} />

        <Tooltip followPointer={true} delay={50}>
          <div>
            <p>
              <span className="payment-label">{tooltip.data.label}</span>
            </p>
            <span className="payment-amount">
              {amount} <small>{'(' + tooltip.data.percent + '%)'}</small>
            </span>
          </div>
          {tooltip.canBeZoomed && (
            <div className="tooltip-footer">
              <i className="i i-hand" />Click to drill down
            </div>
          )}
        </Tooltip>
      </div>
    );
  }
}
