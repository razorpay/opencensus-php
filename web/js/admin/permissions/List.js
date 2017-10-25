import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';

// import { showEntity } from "./Entity";

import { openRoleModal } from './RoleModal';
import { showEntity } from './Entity';

@observer
export default class PermissionsList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'permission_get_multiple',
      count: 1000,
    },
  });

  onSubmit = filters => this.collection.setFilters(filters);

  showEntity = showEntity.bind(null, this.collection);
  showRole = openRoleModal.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Permissions</header>
          <button onClick={this.showEntity}>Add new Permission</button>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Permissions', item => item.name],
  ['Description', item => item.description],
  ['Category', item => item.category],
  ['Actions', item => <Actions item={item} />],
];

const Actions = ({ item }) => (
  <div>
    <div class="link" onClick={item::openRoleModal}>
      Role
    </div>
    <br />
    <div class="link" onClick={item::showEntity}>
      Edit
    </div>
    <br />
    <div class="link danger">Delete</div>
  </div>
);
