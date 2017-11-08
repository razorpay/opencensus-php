import React, { Component } from 'react';

import Table from 'ui/Table';
import { SelectField } from 'ui/Field';

export default class Level extends Component {
  fields = () => {
    let { onDeleteRole, onReviewerCountUpdate, level } = this.props;
    let levelNum = level.level;

    return [
      ['Role', item => this.getRoleName(item)],
      [
        'Minimum approvals',
        item => (
          <input
            type="Number"
            value={item.reviewer_count}
            onChange={e => onReviewerCountUpdate(e, item, levelNum)}
            min="1"
          />
        ),
      ],
      [
        '',
        item => (
          <div class="link danger" onClick={e => onDeleteRole(item, levelNum)}>
            Delete
          </div>
        ),
      ],
    ];
  };

  getRoleName = step => {
    let { allRoles } = this.props;
    let roleIdx = allRoles.findIndex(a => a.id === step.role_id);

    return allRoles[roleIdx].name;
  };

  getPotentialRoles = () => {
    let { allRoles, level } = this.props;
    return allRoles.filter(
      a => level.steps.findIndex(l => l.role_id === a.id) < 0
    );
  };

  render() {
    let {
      level,
      allRoles,
      onRoleSelect,
      onOpTypeUpdate,
      onLevelDelete,
    } = this.props;

    return (
      <div class="box level-container">
        <header>
          Step {level.level}
          <div
            class="link danger level-delete-btn"
            onClick={e => onLevelDelete(level.level)}
          >
            &times;
          </div>
        </header>
        <SelectField
          label="Operation Type"
          value={level.op_type}
          onChange={e => onOpTypeUpdate(e, level.level)}
        >
          <option value="and">AND</option>
          <option value="or">OR</option>
        </SelectField>

        <SelectField
          label="Add a checker Role"
          onChange={e => onRoleSelect(e, level.level)}
        >
          <option value="" />
          {this.getPotentialRoles().map(role => (
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
