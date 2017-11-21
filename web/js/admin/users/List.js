import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch, adminDelete } from 'util/fetch';
import { observer } from 'mobx-react';
import { showEntity } from './Entity';
import { bool } from 'ui/Item';
import { notifyDone } from 'common/modal';
import { prevent } from 'util/index';

import { removeEntity } from './Entity';
import AsyncButton from 'ui/AsyncButton';

@observer
export default class UserList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'admin_get_multiple',
    },
    model: CollectionItem,
  });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Users
            <div class="btn pull-right">
              <Link to="/users/new">Add User</Link>
            </div>
          </header>
        </div>
        <PageTable model={this.collection} fields={fields} href={href} />
      </div>
    );
  }
}

const fields = [
  ['Id', item => item.id],
  ['Name', item => item.name],
  ['Email', item => item.email],
  ['Role', item => item.roles.map(r => r.name).join(', ')],
  ['Locked', item => bool(item.locked)],
  ['Enabled', item => bool(!item.disabled)],
  [
    'Action',
    item => (
      <AsyncButton
        class="link danger m-t m-r"
        pendingClass="link danger m-l btn-pending"
        confirm={`Are you sure you want to delete user id "${item.id}"`}
        onClick={item::removeEntity}
      >
        Delete
        <span class="spin-btn" />
      </AsyncButton>
    ),
  ],
];

const href = item => `/users/${item.id}`;
