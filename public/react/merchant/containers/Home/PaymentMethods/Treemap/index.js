import React, { Component } from 'react';
import { connect } from 'react-redux';

import Tooltip from 'rzp/ui/Tooltip';
import { getFormattedAmount } from 'rzp/utils/rzp-utils';

import renderTreemap from './renderTreemap';

import './styles.styl';

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

    this.scriptsLoaded = false;
    this.onSriptsLoad = null;
    this.treemapApi = null;
    this.componentMounted = false;

    this.onTransition = ::this.onTransition;
    this.handleResize = ::this.handleResize;
    this.onShowTooltip = ::this.onShowTooltip;
    this.onHideTooltip = ::this.onHideTooltip;
  }

  async componentWillMount() {
    const { default: modules } = await import('./asyncModules');

    this.d3 = modules.d3;
    this.bankNames = modules.bankNames;

    this.scriptsLoaded = true;

    if (typeof this.onSriptsLoad === 'function') {
      this.onSriptsLoad();
    }

    // this will be executed when script download is done,
    // if data is ready, and component is already mounted
    // by the time script gets downloaded render treemap
    return (
      this.componentMounted &&
      this.props.data &&
      this.renderTreemap(this.props.data)
    );
  }

  showTooltip({ amount, percent, label }) {
    this.setState({
      tooltip: {
        show: true,
        data: {
          amount,
          percent,
          label,
        },
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

  onShowTooltip({ amount, percent, label }) {
    this.showTooltip({
      amount,
      percent,
      label,
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

  renderTreemap(data) {
    this.treemapApi = renderTreemap(
      this.node,
      data,
      this.d3,
      this.onTransition,
      this.onShowTooltip,
      this.onHideTooltip,
      {},
      this.bankNames
    );

    return (
      typeof this.props.onCSVData === 'function' &&
      this.props.onCSVData(this.treemapApi.csv)
    );
  }

  handleResize() {
    window.clearTimeout(timer);

    this.node.style.width = this.node.parentNode.clientWidth + 'px';

    timer = window.setTimeout(() => {
      this.renderTreemap(this.props.data);
    }, 250);
  }

  componentDidMount() {
    this.componentMounted = true;

    window.addEventListener('resize', this.handleResize);

    return (
      this.props.data &&
      this.state.scriptsLoaded &&
      this.renderTreemap(this.props.data)
    );
  }

  componentWillReceiveProps(nextProps) {
    const { data, currentLevel } = this.props;

    if (data !== nextProps.data) {
      if (!this.scriptsLoaded) {
        return (this.onSriptsLoad = () => {
          return this.renderTreemap(nextProps.data);
        });
      }

      return this.renderTreemap(nextProps.data);
    } else if (
      !this.isNewData &&
      currentLevel &&
      nextProps.currentLevel !== currentLevel
    ) {
      this.treemapApi.transition(nextProps.currentLevel);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  render() {
    const { tooltip } = this.state,
      amount = getFormattedAmount(tooltip.data.amount, true);

    return (
      <div>
        <div ref={node => (this.node = node)} />
        <Tooltip followPointer={true}>
          <div>
            <p>
              {amount} <small>{'(' + tooltip.data.percent + '%)'}</small>
            </p>
            <small>{tooltip.data.label}</small>
          </div>
        </Tooltip>
      </div>
    );
  }
}
