import React, { Component } from 'react';
import axios from 'axios';
import { adminFetch, adminPut } from 'util/fetch';
import OrgForm from './OrganizationForm';
import PermissionsList from './PermissionsList';
import normalize from 'util/normalize';
import { notifyError, notifySuccess } from 'common/modal';

export default class EditOrg extends Component {
  state = {
    org: null,
    permissions: null,
    selectedPerms: null,
    workflowPerms: null,
    pending: true,
  };

  componentWillMount() {
    let { orgId } = this.props.match.params || {};
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
      this.setState({
        selectedPerms,
        workflowPerms,
        permissions,
        org: org || {},
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
        id: this.state.org.id,
      };

      delete data.body.id;
      delete data.body.created_at;
      delete data.body.admin;
      delete data.body.entity;

      return adminPut(data).then(response => {
        if (response) notifySuccess('Org successfully added!');
      });
    } else {
      data.body.admin = {};

      //Add admin props into body
      for (let prop in data.body) {
        if (data.body.hasOwnProperty(prop) && prop.indexOf('admin.') > -1) {
          let adminProp = prop.split('.')[1];
          data.body.admin[adminProp] = data.body[prop];
          delete data.body[prop];
        }
      }

      data.route_name = 'org_create';

      //custom post needed to normalize data
      return axios({
        url: '/admin/generic',
        method: 'post',
        transformRequest: [
          (req, headers) => {
            let newData = normalize.serialize(data);
            headers['Content-Type'] =
              'application/x-www-form-urlencoded; charset=utf-8';
            return newData;
          },
        ],
      })
        .then(response => {
          if (response.data.success) {
            notifySuccess('Org successfully added!');
          } else {
            response.data.errors.map(err => {
              notifyError(err);
            });
          }
        })
        .catch(() => {
          notifyError('Something went wrong!');
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
      return <div class="spinner" />;
    }
    return (
      <div>
        <OrgForm
          {...this.state}
          handleSave={this.handleSave}
          handleAllSelect={this.handleAllSelect}
          handlePermissionSelect={this.handlePermissionSelect}
          handleWorkflowPermissionSelect={this.handleWorkflowPermissionSelect}
        />
      </div>
    );
  }
}
