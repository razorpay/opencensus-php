import React, { Component } from 'react';
import { SelectField } from 'ui/Field';
import Table from 'ui/Table';

export default class Level extends Component {
  fields = item => {
    return [
      ['Role', item => item.name],
      ['Minimum approvals', item => <input type="Number" />],
    ];
  };

  render() {
    let { allRoles, selectedRoles, onSelectRoles } = this.props;
    return (
      <div class="box">
        <header>
          <label htmlFor="">Step</label>
        </header>
        <SelectField label="Operation Type">
          <option value="and">AND</option>
          <option value="or">OR</option>
        </SelectField>

        <SelectField label="Add a checker Role." onChange={onSelectRoles}>
          <option value="" />
          {allRoles.map(role => (
            <option value={role.id} key={role.id}>
              {role.name}
            </option>
          ))}
        </SelectField>
        <br />
        <Table items={selectedRoles} fields={this.fields()} bordered={true} />
      </div>
    );
  }
}
