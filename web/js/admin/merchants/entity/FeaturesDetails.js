import React, { Component } from 'react';
import { toJS } from 'mobx';

import Table from 'ui/Table';

export default class Features extends Component {
  render() {
    if (!Object.keys(this.props.features).length) {
      return <div class="spinner" />;
    }

    let testModeFeatures, liveModeFeatures;

    testModeFeatures = this.props.features['test'] && (
      <Table
        items={this.props.features['test'].assigned_features}
        fields={this.props.getFeaturesFields('test')}
        animateRow={false}
      />
    );

    if (
      this.props.features['live'] &&
      Object.keys(this.props.features['live'].assigned_features).length
    ) {
      liveModeFeatures = (
        <Table
          items={this.props.features['live'].assigned_features}
          fields={this.props.getFeaturesFields('live')}
          animateRow={false}
        />
      );
    }

    return (
      <div>
        {testModeFeatures}
        {liveModeFeatures}
      </div>
    );
  }
}
