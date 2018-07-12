import React, { Component } from 'react';
import Table from 'ui/Table';
import Form from 'ui/Form';
import { SwitchField } from 'ui/Field';
import { openModal } from 'common/modal';
import AddCredits from '../entityModals/AddCredits';

export default class CreditDetails extends Component {
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

  updateCreditsLog(credit) {
    this.props.fetchCreditsLogs(this.state.curMode);
  }

  openEditModal = credit => {
    const { curMode } = this.state;
    const { merchantId } = this.props;

    openModal(
      <AddCredits
        model={{ ...credit, mode: curMode }}
        merchantId={merchantId}
        opts={{
          successHandler: this.updateCreditsLog.bind(this),
        }}
      />
    );
  };

  extraFields() {
    return [
      [
        'Action',
        item => (
          <span class="link" onClick={() => this.openEditModal(item)}>
            {' '}
            Edit{' '}
          </span>
        ),
      ],
    ];
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
            fields={[
              ...this.props.getCreditsFields(this.state.curMode),
              ...this.extraFields(),
            ]}
          />
        </div>
      </Form>
    );
  }
}
