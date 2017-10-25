import React, { Component } from 'react';

import Table from 'ui/Table';
import { adminFetch } from 'util/fetch';
import { openModal } from 'common/modal';
import Collection from 'model/collection';

export default class RoleModal extends Component {
  constructor() {
    super();
    this.state = {
      items: [],
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
      });
    });
  }

  render() {
    return (
      <div>
        <header>Roles</header>
        <Table items={this.state.items} fields={fields} />
      </div>
    );
  }
}

export function openRoleModal() {
  openModal(<RoleModal model={this} />);
}

const fields = [
  ['Name', item => item.name],
  ['Description', item => item.description],
];
