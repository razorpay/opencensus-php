import React, { Component } from 'react';

import Form from 'ui/Form';
import Collection from 'model/collection';
import Field, { SelectField, CheckField, FileField } from 'ui/Field';
import { openSlider, openModal } from 'common/modal';
import Table from 'ui/Table';
import { adminFetch, adminPut } from 'util/fetch';

import OrgForm from './OrgForm';

const permsFields = [
  ['', () => <CheckField />],
  ['Permission', item => item.name],
  ['Category', item => item.merchant_detail],
  ['Assignable', item => <CheckField disabled={!item.assignable} />],
];

class EditOrg extends Component {
  permissions = new Collection({
    data: {
      route_name: 'permission_get_by_type',
      url_params: {
        type: 'assignable', //this.props.model ? "assignable" : "all"
      },
    },
    fetchFn: adminFetch,
  });

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

  editPermissions = () => {
    openModal(
      <div>
        <header>Permissions</header>
        <Table model={this.permissions} fields={permsFields} />
      </div>
    );
  };

  render() {
    return (
      <OrgForm
        {...this.props.model}
        onSubmit={this.save}
        onEditPerms={this.editPermissions}
      />
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditOrg collection={collection} model={this} />);
}
