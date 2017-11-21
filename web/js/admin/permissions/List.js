import React, { Component } from 'react';
import { observer } from 'mobx-react';

import { adminFetch } from 'util/fetch';
import { openRoleModal } from './RoleModal';
import { showEntity, removeEntity } from './Entity';

import Collection from 'model/collection';
import AsyncButton from 'ui/AsyncButton';
import { PageTable } from 'ui/Table';
import CollectionItem from 'model/collectionItem';

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

  showRole = openRoleModal.bind(null, this.collection);

  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Permissions
            <div class="btn pull-right" onClick={this.showEntity}>
              Add a Permission
            </div>
          </header>
        </div>
        <PageTable
          model={this.collection}
          fields={fields}
          onClick={showEntity}
        />
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
    <div class="link m-r" onClick={item::openRoleModal}>
      Roles
    </div>
    <AsyncButton
      class="link danger m-l"
      pendingClass="link danger m-l btn-pending"
      confirm={`Are you sure you want to delete permission id "${item.id}"`}
      onClick={item::removeEntity}
    >
      Delete
      <span class="spin-btn" />
    </AsyncButton>
  </div>
);
