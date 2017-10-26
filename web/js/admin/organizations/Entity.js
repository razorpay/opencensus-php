import React, { Component } from 'react';

import { openSlider, openModal } from 'common/modal';
import { adminFetch, adminPut } from 'util/fetch';

import OrgForm from './OrganizationForm';
import PermissionsList from './PermissionsList';

class EditOrg extends Component {
  state = {
    permissions: null,
    selectedPerms: null,
    workflowPerms: null,
  };

  componentWillMount() {
    let { id } = this.props.model || {};

    let requests = [
      this._fetchFn('permission_get_by_type', { type: 'all' }),
      ...(id
        ? [this._fetchFn('org_get', { id })]
        : [
            null, //Fake request as org_get is not needed for Add
            this._fetchFn('permission_get_by_type', { type: 'assignable' }),
          ]),
    ];

    Promise.all(requests).then(([allPerms, org, assignablePerms]) => {
      let permissions = allPerms.items,
        selectedPerms = {},
        workflowPerms = {};

      //If add new, then assignable perms as selected
      if (!id) {
        assignablePerms.items.forEach(aPerm => {
          selectedPerms[aPerm.id] = true;
        });
      } else {
        org.permissions.forEach(oPerm => {
          selectedPerms[oPerm.id] = true;
        });
        org.workflow_permissions.forEach(wPerm => {
          workflowPerms[wPerm.id] = true;
        });
      }

      this.setState({
        selectedPerms,
        workflowPerms,
        permissions,
      });
    });
  }

  handleAllSelect = isChecked => {
    let { permissions } = this.state;
    let isSelectAllChecked = isChecked,
      selectedPerms = {},
      workflowPerms = this.state.workflowPerms;

    if (!isSelectAllChecked) {
      workflowPerms = {};
    } else {
      permissions.forEach(aPerm => {
        selectedPerms[aPerm.id] = true;
      });
    }

    this.setState({
      selectedPerms,
      workflowPerms,
    });
  };

  handlePermissionSelect = id => {
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

  handleWorkflowPermissionSelect = id => {
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

  openPermissions = () => {
    // openModal(
    //   <PermissionsList
    //     {...this.state}
    //     onAllSelect={this.handleAllSelect}
    //     onPermissionSelect={this.handlePermissionSelect}
    //     onWorkflowPermissionSelect={this.handleWorkflowPermissionSelect}
    //   />
    // );
  };

  _fetchFn = (route, params) => {
    return adminFetch({
      route_name: route,
      url_params: params,
    });
  };

  render() {
    return (
      <div class="orgs-container">
        <OrgForm
          {...this.props.model}
          onSubmit={this.save}
          onEditPerms={this.openPermissions}
          onSave={this.handleSave}
        />
        <PermissionsList
          {...this.state}
          onAllSelect={this.handleAllSelect}
          onPermissionSelect={this.handlePermissionSelect}
          onWorkflowPermissionSelect={this.handleWorkflowPermissionSelect}
        />
        <button type="submit">Save</button>
      </div>
    );
  }
}

export function showEntity(collection) {
  openModal(<EditOrg collection={collection} model={this} />);
}
