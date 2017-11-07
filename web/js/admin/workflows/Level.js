import React, { Component } from 'react';
import { SelectField } from 'ui/Field';
import Table from 'ui/Table';

export default class Level extends Component {
  state = {
    allRoles: this.props.allRoles || [],
    approvalCount: 1,
    level: this.props.level || {},
  };

  updateOpType = e => {
    this.setState({
      level: {
        ...this.state.level,
        op_type: e.target.value,
      },
    });
  };

  updateApprovalNum = e => {
    this.setState({
      approvalCount: e.target.value,
    });
  };

  selectRoles = e => {
    let { allRoles, level } = this.state;

    allRoles.some(a => {
      if (a.id === e.target.value) {
        level.steps = level.steps.concat(a);
        this.setState({
          level: level,
          allRoles: this.state.allRoles.filter(q => q.id !== a.id),
        });
        return 1;
      }
    });
  };

  deleteRoles = role => {
    let { allRoles, level } = this.state;

    level.steps = level.steps.filter(s => s.id !== role.id);
    this.setState({
      allRoles: this.state.allRoles.concat(role),
      level: level,
    });
  };

  fields = item => {
    return [
      ['Role', item => item.name],
      [
        'Minimum approvals',
        item => (
          <input
            type="Number"
            defaultValue={this.state.approvalCount}
            onChange={this.updateApprovalNum}
            min="1"
          />
        ),
      ],
      [
        '',
        item => (
          <div class="link danger" onClick={e => this.deleteRoles(item)}>
            Delete
          </div>
        ),
      ],
    ];
  };

  render() {
    let { allRoles, level } = this.state;
    return (
      <div class="box">
        <header>
          <label htmlFor="">Step {level.level}</label>
        </header>
        <SelectField
          label="Operation Type"
          value={level.op_type}
          onChange={this.updateOpType}
        >
          <option value="and">AND</option>
          <option value="or">OR</option>
        </SelectField>

        <SelectField label="Add a checker Role." onChange={this.selectRoles}>
          <option value="" />
          {allRoles.map(role => (
            <option value={role.id} key={role.id}>
              {role.name}
            </option>
          ))}
        </SelectField>
        <br />
        <Table items={level.steps} fields={this.fields()} bordered={true} />
      </div>
    );
  }
}
