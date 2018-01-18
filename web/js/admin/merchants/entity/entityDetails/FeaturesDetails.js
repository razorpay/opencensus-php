import React, { Component } from 'react';
import { toJS } from 'mobx';

import Table from 'ui/Table';

export default class Features extends Component {
  render() {
    if (!Object.keys(this.props.features).length) {
      return <div class="spinner center" />;
    }

    let testModeFeatures, liveModeFeatures;

    testModeFeatures = this.props.features['test'] && (
      <Table
        items={toJS(this.props.features['test'].assigned_features)}
        fields={this.props.getFeaturesFields('test')}
        animateRow={false}
      />
    );

    console.log(this.props.features['live'].assigned_features);
    liveModeFeatures = this.props.features['live'] && (
      <Table
        items={toJS(this.props.features['live'].assigned_features)}
        fields={this.props.getFeaturesFields('live')}
        animateRow={false}
      />
    );

    return (
      <div>
        {testModeFeatures}
        <br />
        <div class="separate" />
        <br />
        {liveModeFeatures}
      </div>
    );
  }
}
