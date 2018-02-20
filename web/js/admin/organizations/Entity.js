import React, { Component } from 'react';
import fetch from 'common/fetch';
import { adminFetch, adminPut } from 'common/fetch';
import OrgForm from './OrganizationForm';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';

import { adminDelete } from 'common/fetch';

export default class EditOrg extends Component {
  state = {
    permissions: null,
    selectedPerms: null,
    workflowPerms: null,
    pending: true,
  };

  componentWillMount() {
    let { orgId } = this.props.match.params;
    if (orgId === 'new') {
      orgId = null;
    }
    let requests = [
      this._fetchFn('live/permissions/get/all'),
      ...(orgId
        ? [this._fetchFn(`live/orgs/${orgId}`)]
        : [
            null, //Fake request as org_get is not needed for Add
            this._fetchFn('live/permissions/get/assignable'),
          ]),
    ];
    Promise.all(requests).then(([allPerms, org, assignablePerms]) => {
      let permissions = allPerms.items,
        selectedPerms = {},
        workflowPerms = {};
      //If add new, then assignable perms as selected
      if (!orgId) {
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

      this.org = org || {};

      this.setState({
        selectedPerms,
        workflowPerms,
        permissions,
        pending: false,
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

  handleSave = body => {
    let { selectedPerms, workflowPerms } = this.state;

    body.permissions = [];
    body.workflow_permissions = [];
    for (let sPerm in selectedPerms) {
      if (selectedPerms.hasOwnProperty(sPerm)) {
        body.permissions.push(sPerm);
      }
    }

    body.workflow_permissions = body.permissions.filter(
      id => !!workflowPerms[id]
    );
    if (body.id) {
      return adminPut({
        url: `live/orgs/${this.org.id}`,
        content_type: 'application/json',
      }).then(response => {
        if (response) notifySuccess('Org successfully added!');
      });
    } else {
      //Add admin props into body
      for (let prop in body) {
        if (body.hasOwnProperty(prop) && prop.indexOf('admin.') > -1) {
          let adminProp = prop.split('.')[1];
          if (!body.admin) {
            data.body.admin = {};
          }
          body.admin[adminProp] = body[prop];
          delete body[prop];
        }
      }

      return fetch({
        url: 'live/orgs',
        method: 'post',
        data: body,
      }).then(data => {
        if (data) {
          notifySuccess('Org successfully added!');
        }
      });
    }
  };

  _fetchFn = url => adminFetch(url);

  render() {
    if (this.state.pending) {
      return <div class="table-pending" />;
    }
    return (
      <div>
        <OrgForm
          {...this.state}
          org={this.org}
          handleSave={this.handleSave}
          handleAllSelect={this.handleAllSelect}
          handlePermissionSelect={this.handlePermissionSelect}
          handleWorkflowPermissionSelect={this.handleWorkflowPermissionSelect}
        />
      </div>
    );
  }
}

export function removeEntity() {
  return adminDelete(`orgs/${this.id}`).then(response => {
    notifyDone();
    this.collection.items.remove(this);

    return response;
  });
}
