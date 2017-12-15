import React, { Component } from 'react';
import fetch from 'util/fetch';
import { adminFetch, adminPut } from 'util/fetch';
import OrgForm from './OrganizationForm';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';

import { adminDelete } from 'util/fetch';

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
      this._fetchFn('permission_get_by_type', { type: 'all' }),
      ...(orgId
        ? [this._fetchFn('org_get', { id: orgId })]
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
    let data = { body };
    let { selectedPerms, workflowPerms } = this.state;

    data.body.permissions = [];
    data.body.workflow_permissions = [];
    for (let sPerm in selectedPerms) {
      if (selectedPerms.hasOwnProperty(sPerm)) {
        data.body.permissions.push(sPerm);
      }
    }

    data.body.workflow_permissions = data.body.permissions.filter(
      id => !!workflowPerms[id]
    );
    if (data.body.id) {
      data['content_type'] = 'application/json';
      data['route_name'] = 'org_edit';
      data['url_params'] = {
        id: this.org.id,
      };

      delete data.body.id;
      delete data.body.created_at;
      delete data.body.admin;
      delete data.body.entity;

      return adminPut(data).then(response => {
        if (response) notifySuccess('Org successfully added!');
      });
    } else {
      //Add admin props into body
      for (let prop in data.body) {
        if (data.body.hasOwnProperty(prop) && prop.indexOf('admin.') > -1) {
          let adminProp = prop.split('.')[1];
          if (!data.body.admin) {
            data.body.admin = {};
          }
          data.body.admin[adminProp] = data.body[prop];
          delete data.body[prop];
        }
      }

      data.route_name = 'org_create';

      return fetch({
        url: '/admin/generic',
        method: 'post',
        data,
      }).then(data => {
        if (data) {
          notifySuccess('Org successfully added!');
        }
      });
    }
  };

  _fetchFn = (route, params) => {
    return adminFetch({
      route_name: route,
      url_params: params,
    });
  };

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
  let params = {
    route_name: 'org_delete',
    url_params: {
      id: this.id,
    },
  };

  return adminDelete(params).then(response => {
    notifyDone();
    this.collection.items.remove(this);

    return response;
  });
}
