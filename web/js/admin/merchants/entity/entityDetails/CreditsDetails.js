import React, { Component } from 'react';

import { notifyError, notifySuccess } from 'common/modal';
import Table from 'ui/Table';
import Form from 'ui/Form';
import { SelectField, SwitchField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';

export default class PricingPlanModal extends Component {
  state = { curMode: 'live' };

  componentWillMount() {
    this.fetchCreditsIfAbsent(this.state.curMode);
  }

  handleChange = e => {
    this.setState({ curMode: e.target.value });
    this.fetchCreditsIfAbsent(e.target.value);
  };

  fetchCreditsIfAbsent(mode) {
    if (!this.props.creditsLogs[mode]) {
      this.props.fetchCreditsLogs(mode);
    }
  }

  render() {
    return (
      <Form>
        <SwitchField
          name="mode"
          disabledValue="live"
          enabledValue="test"
          disabledLabel="Live"
          enabledLabel="Test"
          onChange={this.handleChange}
        />

        <div>
          <Table
            items={this.props.creditsLogs[this.state.curMode]}
            fields={this.props.getCreditsFields(this.state.curMode)}
          />
        </div>
      </Form>
    );
  }
}
