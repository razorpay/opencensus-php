import React, { Component } from 'react';

import { openSlider, openModal } from 'common/modal';

import { adminPut } from 'util/fetch';

import OrgForm from './OrganizationForm';
import PermissionsList from './PermissionsList';

class EditOrg extends Component {
  save = body => {
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
    openModal(
      <PermissionsList orgId={this.props.model && this.props.model.id} />
    );
  };

  render() {
    return (
      <OrgForm
        {...this.props.model}
        onSubmit={this.save}
        onEditPerms={this.openPermissions}
      />
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditOrg collection={collection} model={this} />);
}
