import React, { Component } from 'react';
import Table from 'ui/Table';

export default class OrgTable extends Component {
  state = {
    orgs: this.props.orgs || [],
    workflow_orgs: this.props.workflowOrgs || [],
  };

  selectOrg = e => {
    let active = e.target.checked;
    let id = e.target.value;
    let orgs = this.state.orgs;
    let state;

    if (active) {
      state = {
        orgs: orgs.concat(id),
      };
    } else {
      orgs.splice(orgs.indexOf(id), 1);
      state = {
        orgs,
        workflow_orgs: this.filterWorkflowOrgs(orgs),
      };
    }
    this.setState(state);
    this.props.onChange(state);
  };

  selectWorkflowOrg = e => {
    let active = e.target.checked;
    let id = e.target.value;
    let worgs = this.state.workflow_orgs;
    let state;

    if (active) {
      state = {
        workflow_orgs: worgs.concat(id),
      };
    } else {
      worgs.splice(worgs.indexOf(id), 1);
      state = {
        workflow_orgs: worgs,
      };
    }

    this.setState(state);
    this.props.onChange({ ...state, orgs: this.state.orgs });
  };

  toggleAll = e => {
    let active = e.target.checked;
    let orgs = active ? this.props.items.map(i => i.id) : [];
    let state = {
      orgs,
      workflow_orgs: this.filterWorkflowOrgs(orgs),
    };

    this.setState(state);
    this.props.onChange(state);
  };

  // make sure no enabled orgs are present as workflow_orgs
  filterWorkflowOrgs(orgs) {
    return this.state.workflow_orgs.filter(wo => orgs.indexOf(wo) !== -1);
  }

  fields() {
    return [
      [
        <input
          type="checkbox"
          checked={
            this.props.items.length &&
            this.state.orgs.length === this.props.items.length
          }
          onChange={this.toggleAll}
        />,
        item => (
          <input
            type="checkbox"
            checked={this.state.orgs.indexOf(item.id) !== -1}
            value={item.id}
            onChange={this.selectOrg}
          />
        ),
      ],
      ['Business Name', item => item.business_name],
      ['Display Name', item => item.display_name],
      [
        'Enable Workflow?',
        item => (
          <input
            type="checkbox"
            disabled={this.state.orgs.indexOf(item.id) === -1}
            checked={this.state.workflow_orgs.indexOf(item.id) !== -1}
            value={item.id}
            onChange={this.selectWorkflowOrg}
          />
        ),
      ],
    ];
  }

  render() {
    let { items, onChange } = this.props;

    let { orgs, workflow_orgs } = this.state;

    return <Table fields={this.fields()} items={items} />;
  }
}
