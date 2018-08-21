import React, { Component } from 'react';
import fetch from 'common/fetch';
import {
  adminFetch,
  adminFormUpload,
  adminFormUpload2,
  adminPut,
  adminDelete,
} from 'common/fetch';
import OrgForm from './OrganizationForm';
import { notifyError, notifySuccess } from 'common/modal';

export default class EditOrg extends Component {
  state = {
    permissions: null,
    selectedPerms: null,
    workflowPerms: null,
    filesURL: {},
  };

  prepareOrgs = props => {
    let { orgId } = props.match.params;

    if (orgId === 'new') {
      orgId = null;
    }

    this.setState({
      pending: true,
    });

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
        workflowPerms = {},
        filesURL = {};
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

        filesURL = {
          main_logo_url: org.main_logo_url,
          invoice_logo_url: org.invoice_logo_url,
          login_logo_url: org.login_logo_url,
        };
      }

      this.org = org || {};

      this.setState({
        selectedPerms,
        workflowPerms,
        permissions,
        filesURL,
        pending: false,
      });
    });
  };

  componentWillMount() {
    this.prepareOrgs(this.props);
  }

  componentDidUpdate(prevProps) {
    if (this.props.match.params.orgId !== prevProps.match.params.orgId) {
      this.prepareOrgs(this.props);
    }
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
    let { selectedPerms, workflowPerms, filesURL } = this.state;

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

    if (body.email_domains) {
      body.email_domains = body.email_domains.replace(/ /g, '').split(',');
    }

    if (body.hostname) {
      body.hostname = body.hostname.replace(/ /g, '');
    }

    if (body.id) {
      //delete unwanted props
      delete body.id;

      ['login_logo_url', 'main_logo_url', 'invoice_logo_url'].map(prop => {
        if (filesURL[prop]) {
          body[prop] = filesURL[prop];
        }
      });

      return adminPut({
        url: `live/orgs/${this.org.id}`,
        content_type: 'application/json',
        data: body,
      }).then(response => {
        if (response) notifySuccess('Org successfully added!');
      });
    } else {
      //Add admin props into body
      for (let prop in body) {
        if (body.hasOwnProperty(prop) && prop.indexOf('admin.') > -1) {
          let adminProp = prop.split('.')[1];
          if (!body.admin) {
            body.admin = {};
          }
          body.admin[adminProp] = body[prop];
          delete body[prop];
        }
      }

      //- for array of permissions
      return adminFormUpload2(body, '/admin/api/live/orgs').then(resp => {
        if (resp.data.success) {
          notifySuccess('Org successfully added!');
          setTimeout(() => {
            this.props.history.replace(`/orgs/${resp.data.data.id}`);
          }, 1000);
        } else {
          notifyError(resp.data.errors[0]);
        }
      });
    }
  };

  handleFileUpload = (file, fileName, type) => {
    const { filesURL } = this.state;

    let fileData = {
      [fileName]: file,
      type,
    };

    return adminFormUpload(fileData, `/admin/org/${this.org.id}`).then(resp => {
      if (resp.data.success) {
        notifySuccess('File uploaded successfully');

        this.setState({
          filesURL: {
            ...filesURL,
            [`${fileName}_url`]: resp.data.data,
          },
        });
      } else {
        notifyError(resp.data.errors[0]);
      }
    });
  };

  _fetchFn = url => adminFetch(url);

  render() {
    if (this.state.pending) {
      return <div class="spinner center" />;
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
          onFileUpload={this.handleFileUpload}
        />
      </div>
    );
  }
}

export function removeEntity() {
  return adminDelete(`live/orgs/${this.id}`).then(response => {
    if (response) {
      notifySuccess('Organisation is removed successfully');
      this.collection.items.remove(this);
    }
    return response;
  });
}
