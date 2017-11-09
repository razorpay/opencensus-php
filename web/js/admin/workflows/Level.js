import React, { Component } from 'react';

import Table from 'ui/Table';
import { SelectField } from 'ui/Field';
import { observable } from 'mobx';
import { observer } from 'mobx-react';

function updateReviewer(e) {
  this.reviewer_count = e.target.value;
}

@observer
export default class Levels extends Component {
  componentWillMount() {
    this.roleMap = this.props.roles.reduce((prev, next) => {
      prev[next.id] = next;
      return prev;
    }, {});
  }

  render() {
    let levels = this.props.levels;
    return (
      <div class="column">
        {levels.map((level, index) => (
          <Level
            levels={levels}
            level={level}
            key={index}
            index={index}
            roleMap={this.roleMap}
            roles={this.props.roles}
          />
        ))}
        <div class="btn" onClick={this.addLevel}>
          Add Step
        </div>
      </div>
    );
  }

  addLevel = e => {
    this.props.levels.push({
      op_type: 'and',
      steps: [],
    });
  };
}

@observer
class Level extends Component {
  roleFields = [
    [
      'Role',
      item => {
        let role = this.props.roleMap[item.role_id];
        return (role && role.name) || <i>(Role Deleted)</i>;
      },
    ],
    [
      'Minimum approvals',
      item => (
        <input
          type="number"
          min="1"
          defaultValue={item.reviewer_count}
          onChange={item::updateReviewer}
        />
      ),
    ],
    [
      '',
      item => (
        <div
          class="link danger"
          onClick={e => this.props.level.steps.remove(item)}
        >
          Delete
        </div>
      ),
    ],
  ];

  addRole = e => {
    this.props.level.steps.push({
      role_id: e.target.value,
      reviewer_count: 1,
    });
  };

  delete = e => this.props.levels.remove(this.props.level);

  render() {
    let { level, roles, index } = this.props;

    let selectedRoles = level.steps.map(s => s.role_id);
    let potentialRoles = roles.filter(s => selectedRoles.indexOf(s.id) < 0);
    return (
      <div class="box" key={index}>
        <header>
          Step {index + 1}
          <i class="delete i-trash" onClick={this.delete} />
        </header>
        <SelectField label="Operation Type" defaultValue={level.op_type}>
          <option value="and">AND</option>
          <option value="or">OR</option>
        </SelectField>
        <SelectField label="Select Role" onChange={this.addRole} value="">
          <option value="" />
          {potentialRoles.map(role => (
            <option value={role.id} key={role.id}>
              {role.name}
            </option>
          ))}
        </SelectField>
        <Table
          animateRow={false}
          fields={this.roleFields}
          items={level.steps}
        />
      </div>
    );
  }
}
