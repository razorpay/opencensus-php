import React, { Component } from 'react';
import { connect } from 'react-redux';

import Tooltip from 'rzp/ui/Tooltip';
import { getFormattedAmountNew } from 'rzp/utils/rzp-utils';
import { globalGroupTitleMap as groupTitleMap } from "rzp/utils/pokedex";

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
      window.d3,
      this.onTransition,
      this.onShowTooltip,
      this.onHideTooltip,
      groupTitleMap,
      bankNames
    );

    return (
      typeof this.props.onCSVData === 'function' &&
      this.props.onCSVData(this.treemapApi.csv)
    );
  }

  handleResize() {
    window.clearTimeout(timer);

    const parent = this.node.parentNode;

    // if resize is only vertical
    if (this.node.clientWidth === parent.clientWidth) {
    
      return;
    }

    this.node.style.width = parent.clientWidth + 'px';

    timer = window.setTimeout(() => {
      this.renderTreemap(this.props.data);
    }, 250);
  }

  componentDidMount() {
    this.componentMounted = true;

    window.addEventListener('resize', this.handleResize);

    return (
      this.props.data &&
      this.renderTreemap(this.props.data)
    );
  }

  componentWillReceiveProps(nextProps) {
    const { data, currentLevel } = this.props;

    if (data !== nextProps.data) {

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
      amount = getFormattedAmountNew(tooltip.data.amount, true);

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

const bankNames = {"AIRP":"Airtel Payments Bank","ALLA":"Allahabad Bank","ANDB":"Andhra Bank","UTIB":"Axis Bank","UTIB_C":"Axis Bank - Corporate Banking","BBKM":"Bank of Bahrein and Kuwait","BARB":"Bank of Baroda","BARB_C":"Bank of Baroda - Corporate Banking","BARB_R":"Bank of Baroda - Retail Banking","BKID":"Bank of India","MAHB":"Bank of Maharashtra","CITI":"CITI Bank","CNRB":"Canara Bank","CSBK":"Catholic Syrian Bank","CBIN":"Central Bank of India","CIUB":"City Union Bank","CORP":"Corporation Bank","COSB":"Cosmos Co-operative Bank","DCBL":"DCB Bank","BKDN":"Dena Bank","DEUT":"Deutsche Bank","DBSS":"Development Bank of Singapore","DLXB":"Dhanlaxmi Bank","FDRL":"Federal Bank","HDFC":"HDFC Bank","ICIC":"ICICI Bank","ICIC_C":"ICICI Bank - Corporate Banking","IBKL":"IDBI","IDFB":"IDFC Bank","IDIB":"Indian Bank","IOBA":"Indian Overseas Bank","INDB":"Indusind Bank","JAKA":"Jammu and Kashmir Bank","JSBP":"Janata Sahakari Bank (Pune)","KARB":"Karnataka Bank","KVBL":"Karur Vysya Bank","KKBK":"Kotak Mahindra Bank","LAVB_C":"Lakshmi Vilas Bank - Corporate Banking","LAVB_R":"Lakshmi Vilas Bank - Retail Banking","NKGS":"NKGSB Co-operative Bank","ORBC":"Oriental Bank of Commerce","PMCB":"Punjab & Maharashtra Co-operative Bank","PSIB":"Punjab & Sind Bank","PUNB":"Punjab National Bank","PUNB_C":"Punjab National Bank - Corporate Banking","PUNB_R":"Punjab National Bank - Retail Banking","RATN":"RBL Bank","SRCB":"Saraswat Co-operative Bank","SVCB":"Shamrao Vithal Co-operative Bank","SIBL":"South Indian Bank","SCBL":"Standard Chartered Bank","SBBJ":"State Bank of Bikaner and Jaipur","SBHY":"State Bank of Hyderabad","SBIN":"State Bank of India","SBMY":"State Bank of Mysore","STBP":"State Bank of Patiala","SBTR":"State Bank of Travancore","SYNB":"Syndicate Bank","TMBL":"Tamilnadu Mercantile Bank","TNSC":"Tamilnadu State Apex Co-operative Bank","UCBA":"UCO Bank","UBIN":"Union Bank of India","UTBI":"United Bank of India","VIJB":"Vijaya Bank","YESB":"Yes Bank"}
