import React, { Component } from 'react';
import renderTreemap from './renderTreemap';

export default class Treemap extends Component {
  constructor(props) {
    super(props);

    this.state = {
      scriptsLoaded: false,
    };

    this.treemapApi = null;
    this.onTransition = ::this.onTransition;
  }

  async componentWillMount() {
    this.d3 = await import('d3');

    this.setState({ scriptsLoaded: true });

    //TODO: render treemap if data is ready
    return this.props.data && renderTreemap(data);
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
    return this.renderTreemap(this.props.data);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.data !== nextProps.data) {
      return this.renderTreemap(nextProps.data);
    }
  }

  render() {
    return <div ref={node => (this.node = node)} />;
  }
}
