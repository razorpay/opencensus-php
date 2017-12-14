import React, { Component } from 'react';
import renderTreemap from './renderTreemap';

import './styles.styl';

export default class Treemap extends Component {
  constructor(props) {
    super(props);

    this.state = {
      scriptsLoaded: false,
    };

    this.treemapApi = null;
    this.componentMounted = false;

    this.onTransition = ::this.onTransition;
  }

  async componentWillMount() {
    this.d3 = await import('d3');

    this.setState({ scriptsLoaded: true });

    // this will be executed when script download is done,
    // if data is ready by the time script gets downloaded,
    // render treemap
    return (
      this.componentMounted && this.props.data && renderTreemap(this.props.data)
    );
  }

  onTransition(d) {
    const { onLevelChange } = this.props;

    return typeof onLevelChange === 'function' && onLevelChange(d);
  }

  renderTreemap(data) {
    if (data && this.state.scriptsLoaded) {
      this.treemapApi = renderTreemap(
        this.node,
        data,
        this.d3,
        this.onTransition
      );
    }
  }

  componentDidMount() {
    this.componentMounted = true;
    return this.renderTreemap(this.props.data);
  }

  componentWillReceiveProps(nextProps) {
    const { data, currentLevel } = this.props;

    if (data !== nextProps.data) {
      return this.renderTreemap(nextProps.data);
    } else if (currentLevel && nextProps.currentLevel !== currentLevel) {
      this.treemapApi.transition(nextProps.currentLevel);
    }
  }

  render() {
    return <div ref={node => (this.node = node)} />;
  }
}
