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

@observer
export default class UserList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'admin_get_multiple',
    },
    model: CollectionItem,
  });

  onSubmit = filters => this.collection.setFilters(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Users
            <div class="btn">
              <Link to="/users/new">Add User</Link>
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
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
      <div class="link danger" onClick={item::removeEntity}>
        Delete
      </div>
    ),
  ],
];

export function removeEntity(e) {
  prevent(e);
  let params = {
    route_name: 'admin_delete',
    url_params: {
      adminId: this.id,
    },
  };
  adminDelete(params).then(response => {
    if (response) {
      this.collection.items.remove(this);
      notifyDone();
    }
  });
}

const href = item => `/users/${item.id}`;
