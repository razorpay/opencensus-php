import React, { Component } from 'react';
import axios from 'axios';
import Table from 'ui/Table';
import { CheckField } from 'ui/Field';
import { adminFetch } from 'util/fetch';

class PermissionsList extends Component {
  state = {
    selectedPerms: null,
    workflowPerms: null,
    isSelectAllChecked: false,
  };

  componentWillMount() {
    let selectedPerms = {},
      workflowPerms = {};

    let {
      orgPerms,
      allPerms,
      assignablePerms,
      orgWorkflowPerms,
      isAdd,
    } = this.props;

    if (!isAdd) {
      assignablePerms.forEach(aPerm => {
        selectedPerms[aPerm.id] = true;
      });
    } else {
      orgPerms.forEach(oPerm => {
        selectedPerms[oPerm.id] = true;
      });
      orgWorkflowPerms.forEach(wPerm => {
        workflowPerms[wPerm.id] = true;
      });
    }

    this.setState({ selectedPerms, workflowPerms });
  }

  selectAllPermission = () => {
    let { allPerms } = this.props;
    let isSelectAllChecked = !this.state.isSelectAllChecked,
      selectedPerms = {},
      workflowPerms = this.state.workflowPerms;

    if (!isSelectAllChecked) {
      workflowPerms = {};
    } else {
      allPerms.forEach(aPerm => {
        selectedPerms[aPerm.id] = true;
      });
    }

    this.setState({
      isSelectAllChecked,
      selectedPerms,
      workflowPerms,
    });
  };

  selectPermission = id => {
    let { selectedPerms, workflowPerms } = this.state;

    if (workflowPerms[id]) {
      delete workflowPerms[id];
    }

    if (selectedPerms[id]) {
      delete selectedPerms[id];
    } else {
      selectedPerms[id] = true;
    }

    this.setState({
      ...this.state,
      selectedPerms,
      workflowPerms,
    });
  };

  selectWorkflowPermission = id => {
    let { workflowPerms } = this.state;

    if (workflowPerms[id]) {
      delete workflowPerms[id];
    } else {
      workflowPerms[id] = true;
    }

    this.setState({
      ...this.state,
      workflowPerms,
    });
  };

  permFields = () => {
    let { selectedPerms, workflowPerms, isSelectAllChecked } = this.state;
    return [
      [
        <input
          type="checkbox"
          onChange={this.selectAllPermission}
          checked={isSelectAllChecked}
        />,
        item => (
          <input
            class="some"
            type="checkbox"
            checked={!!selectedPerms[item.id]}
            onChange={() => {
              this.selectPermission(item.id);
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
              this.selectWorkflowPermission(item.id);
            }}
          />
        ),
      ],
      ['Assignable', item => (item.assignable ? 'Yes' : 'No')],
    ];
  };

  render() {
    return (
      <div>
        <header>Permissions</header>
        <Table items={this.props.allPerms} fields={this.permFields()} />
      </div>
    );
  }
}

export default PermissionsList;
