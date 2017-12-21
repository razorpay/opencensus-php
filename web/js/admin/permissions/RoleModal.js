import React, { Component } from 'react';

import Table from 'ui/Table';
import { adminFetch } from 'common/fetch';
import { openModal } from 'common/modal';
import BaseModal from 'ui/BaseModal';
import { prevent } from 'common/util';

export default class RoleModal extends Component {
  constructor() {
    super();
    this.state = {
      items: [],
      pending: true,
    };
  }

  componentWillMount() {
    adminFetch({
      route_name: 'permission_get_roles',
      url_params: {
        id: this.props.model.id,
      },
    }).then(response => {
      this.setState({
        items: response.items,
        pending: false,
      });
    });
  }

  render() {
    let { items, pending } = this.state;

    return (
      <BaseModal header="Roles">
        <Table pending={pending} items={items} fields={fields} />
      </BaseModal>
    );
  }
}

export function openRoleModal(e) {
  prevent(e);
  openModal(<RoleModal model={this} />);
}

const fields = [
  ['Name', item => item.name],
  ['Description', item => item.description],
];
