import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { observer } from 'mobx-react';
import { showEntity, removeEntity } from './Entity';
import CollectionItem from 'model/collectionItem';

import AsyncButton from 'ui/AsyncButton';

@observer
export default class GroupList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'group_get_multiple',
    },
    model: CollectionItem,
  });
  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Groups
            <div class="btn pull-right" onClick={this.showEntity}>
              Add Group
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
  ['Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
  [
    'Action',
    item => (
      <AsyncButton
        class="link danger m-t m-r"
        pendingClass="link danger-faded m-l btn-pending"
        confirm={`Are you sure you want to delete user id "${item.id}"`}
        onClick={item::removeEntity}
      >
        Delete
        <span class="dot-loader">.</span>
      </AsyncButton>
    ),
  ],
];
