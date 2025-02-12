import React, { Component } from 'react';
import { connect } from 'react-redux';

import Tooltip from 'common/ui/Tooltip';
import { getI18nifyFormattedNumber } from 'common/utils/numerals';
import { globalGroupTitleMap as groupTitleMap } from 'common/utils/pokedex';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { bankNames } from 'merchant/containers/Home/PaymentMethods/data';

import renderTreemap from './renderTreemap';

let timer = null;

class Treemap extends Component {
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

    this.onTransition = this.onTransition.bind(this);
    this.handleResize = this.handleResize.bind(this);
    this.onShowTooltip = this.onShowTooltip.bind(this);
    this.onHideTooltip = this.onHideTooltip.bind(this);
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
      window.d3,
      this.onTransition,
      this.onShowTooltip,
      this.onHideTooltip,
      groupTitleMap,
      bankNames,
      this.props.user,
    );

    return typeof this.props.onCSVData === 'function' && this.props.onCSVData(this.treemapApi.csv);
  }

  handleResize() {
    window.clearTimeout(timer);

    const parent = this.node.parentNode;

    this.node.style.width = `${parent.clientWidth}px`;

    timer = window.setTimeout(() => {
      this.renderTreemap(this.props.data, this.props.isCurrency);
    }, 250);
  }

  componentDidMount() {
    this.componentMounted = true;

    window.addEventListener('resize', this.handleResize);

    return this.props.data && this.renderTreemap(this.props.data, this.props.isCurrency);
  }

  // eslint-disable-next-line consistent-return
  UNSAFE_componentWillReceiveProps(nextProps) {
    const { data, currentLevel } = this.props;

    if (data !== nextProps.data) {
      return this.renderTreemap(nextProps.data, nextProps.isCurrency);
    } else if (!this.isNewData && currentLevel && nextProps.currentLevel !== currentLevel) {
      return this.treemapApi.transition(nextProps.currentLevel);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  render() {
    const { tooltip } = this.state;
    const { isCurrency, user } = this.props;
    const amount = (isCurrency ? getFormattedAmountNew : getI18nifyFormattedNumber)(
      tooltip.data.amount,
      true,
      user.merchant.currency,
    );

    return (
      <div>
        <div id="payment-methods-treemap" ref={(node) => (this.node = node)} />

        <Tooltip followPointer={true} delay={50} currency={user.merchant.currency}>
          <div>
            <p>
              <span className="payment-label">{tooltip.data.label}</span>
            </p>
            <span className="payment-amount">
              {amount} <small>{`(${tooltip.data.percent}%)`}</small>
            </span>
          </div>
          {tooltip.canBeZoomed && (
            <div className="tooltip-footer">
              <i className="i i-hand" />
              Click to drill down
            </div>
          )}
        </Tooltip>
      </div>
    );
  }
}

export default connect((state) => ({ user: state.session.user }), null)(Treemap);
