import React, { Component } from 'react';
import renderTreemap from './renderTreemap';
import { connect } from 'react-redux';

import './styles.styl';

let timer = null;

@connect(null, null)
export default class Treemap extends Component {
  constructor(props) {
    super(props);

    this.state = {
      scriptsLoaded: false,
    };

    this.treemapApi = null;
    this.componentMounted = false;

    this.onTransition = ::this.onTransition;
    this.handleResize = ::this.handleResize;
  }

  async componentWillMount() {
    const { default: modules } = await import('./asyncModules');

    this.d3 = modules.d3;
    this.bankNames = modules.bankNames;

    this.setState({ scriptsLoaded: true });

    // this will be executed when script download is done,
    // if data is ready, and component is already mounted
    // by the time script gets downloaded render treemap
    return (
      this.componentMounted &&
      this.props.data &&
      this.renderTreemap(this.props.data)
    );
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
    return <div ref={node => (this.node = node)} />;
  }
}
