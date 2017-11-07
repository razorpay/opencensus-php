import React, { Component } from 'react';
import Field, { SelectField } from 'ui/Field';
import Table from 'ui/Table';

import Level from './Level';

export default class WorkflowForm extends Component {
  permFields = item => {
    let { onDeletePerms } = this.props;
    return [
      ['Name', item => item.name],
      [
        'Action',
        item => (
          <div class="link danger" onClick={e => onDeletePerms(item)}>
            Remove
          </div>
        ),
      ],
    ];
  };

  render() {
    let {
      levels,
      allPerms,
      allRoles,
      selectedPerms,
      onSelectPerms,
      onStepsAdd,
    } = this.props;

    return (
      <div class="workflow-container box">
        <header>Create Workflow:</header>
        {/* <header>{model ? "Edit" : "Create"} Workflow</header> */}
        <div class="split">
          <Field label="Workflow Name" name="name" />
          <SelectField label="Actions List" onChange={onSelectPerms} value="">
            <option value="" />
            {allPerms.map(perm => (
              <option value={perm.id} key={perm.id}>
                {perm.name}
              </option>
            ))}
          </SelectField>
          {selectedPerms.length ? (
            <Table
              items={selectedPerms}
              fields={this.permFields()}
              bordered={true}
            />
          ) : null}
        </div>
        <div class="split">
          <div class="level-container" />
          <div class="btn" onClick={onStepsAdd}>
            + Add a Step
          </div>
          <div class="btn">Save Changes</div>
          {levels.length
            ? levels.map((level, idx) => (
                <Level key={idx} allRoles={allRoles} level={level} />
              ))
            : null}
        </div>
      </div>
    );
  }
}
