import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';

import { openRoleModal } from './RoleModal';
import { showEntity, removeEntity } from './Entity';

@observer
export default class PermissionsList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'permission_get_multiple',
      count: 1000,
    },
    model: CollectionItem,
  });

  onSubmit = filters => this.collection.setFilters(filters);

  showRole = openRoleModal.bind(null, this.collection);

  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Permissions
            <div class="btn" onClick={this.showEntity}>
              Add a Permission
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} />
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
      Roles
    </div>
    <div class="link" onClick={item::showEntity}>
      Edit
    </div>
    <div class="link danger" onClick={item::removeEntity}>
      Delete
    </div>
  </div>
);
