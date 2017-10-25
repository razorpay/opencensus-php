import React, { Component } from 'react';

import { openSlider, openModal } from 'common/modal';
import { adminFetch, adminPut } from 'util/fetch';

import OrgForm from './OrganizationForm';
import PermissionsList from './PermissionsList';

class EditOrg extends Component {
  state = {
    org: null,
    allPerms: null,
    assignablePerms: null,
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
      let newState = {};

      if (id) {
        newState = { allPerms, org };
      } else {
        newState = { allPerms, assignablePerms };
      }
      this.setState({
        ...this.state,
        ...newState,
      });
    });
  }

  _fetchFn = (route, params) => {
    return adminFetch({
      route_name: route,
      url_params: params,
    });
  };

  handleSave = body => {
    let params = {
      content_type: 'application/json',
      route_name: 'org_edit',
      body: body,
    };

    if (this.props.model) {
      params.url_params = {
        id: this.props.model.id,
      };
    }

    return adminPut(params);
  };

  openPermissions = () => {
    let { allPerms, org, assignablePerms } = this.state;
    openModal(
      <PermissionsList
        allPerms={allPerms.items}
        orgPerms={org && org.permissions}
        orgWorkflowPerms={org && org.workflow_permissions}
        assignablePerms={assignablePerms && assignablePerms.items}
        isAdd={!!org}
      />
    );
  };

  render() {
    return (
      <OrgForm
        {...this.props.model}
        onSubmit={this.save}
        onEditPerms={this.openPermissions}
        onSave={this.handleSave}
      />
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditOrg collection={collection} model={this} />);
}
