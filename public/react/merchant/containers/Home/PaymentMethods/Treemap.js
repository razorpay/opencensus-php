import React, { Component } from 'react';
import renderTreemap from './renderTreemap';

export default class Treemap extends Component {
  constructor(props) {
    super(props);

    this.state = {
      scriptsLoaded: false,
    };
  }

  async componentWillMount() {
    this.d3 = await import('d3');

    this.setState({ scriptsLoaded: true });
  }

  renderTreemap(data) {
    return (
      data &&
      this.state.scriptsLoaded &&
      renderTreemap(this.node, data, this.d3)
    );
  }

  componentDidMount() {
    return this.renderTreemap(this.props.data);
  }

  componentWillReceiveProps(nextProps) {
    return this.renderTreemap(nextProps.data);
  }

  render() {
    return <div ref={node => (this.node = node)} />;
  }
}
