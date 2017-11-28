import React, { Component } from 'react';
import Table from 'ui/Table';
import { CheckField } from 'ui/Field';
import { adminFetch } from 'util/fetch';

class PermissionsList extends Component {
  state = {
    isSelectAllChecked: false,
  };

  handleSelectAll = () => {
    let { isSelectAllChecked } = this.state;
    this.setState(
      {
        isSelectAllChecked: !isSelectAllChecked,
      },
      () => {
        this.props.onAllSelect(this.state.isSelectAllChecked);
      }
    );
  };

  permissionFields = () => {
    let {
      selectedPerms,
      workflowPerms,
      onPermissionSelect,
      onWorkflowPermissionSelect,
    } = this.props;
    return [
      [
        <input
          type="checkbox"
          onChange={this.handleSelectAll}
          checked={this.state.isSelectAllChecked}
        />,
        item => (
          <input
            class="some"
            type="checkbox"
            checked={!!selectedPerms[item.id]}
            onChange={() => {
              onPermissionSelect(item.id);
            }}
          />
        ),
      ],
      ['Permission', item => item.name],
      ['Category', item => item.category],
      ['Description', item => item.description],
      [
        'Workflow Enable',
        item => (
          <input
            type="checkbox"
            disabled={!selectedPerms[item.id]}
            checked={!!workflowPerms[item.id]}
            onChange={() => {
              onWorkflowPermissionSelect(item.id);
            }}
          />
        ),
      ],
      ['Assignable', item => (item.assignable ? 'Yes' : 'No')],
    ];
  };

  render() {
    return (
      <div class="perms-container">
        <header>Select Permissions:</header>
        <Table
          items={this.props.permissions}
          fields={this.permissionFields()}
        />
      </div>
    );
  }
}

export default PermissionsList;
