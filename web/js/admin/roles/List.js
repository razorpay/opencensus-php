import React, { Component } from 'react';
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
export default class RoleList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'role_get_multiple',
    },
    model: CollectionItem,
  });

  state = {
    pending: true,
  };

  componentWillMount() {
    adminFetch({
      route_name: 'permission_get_multiple',
    }).then(data => {
      this.setState({
        pending: false,
      });
    });
  }

  showEntity = showEntity.bind(null, this.collection);

  render() {
    if (this.state.pending) {
      return <div class="table-pending" />;
    }
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Roles
            <div class="btn pull-right" onClick={this.showEntity}>
              Add Role
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
      <div class="link danger" onClick={item::removeEntity}>
        Delete
      </div>
    ),
  ],
];

export function removeEntity(e) {
  prevent(e);
  let params = {
    route_name: 'role_delete',
    url_params: {
      roleId: this.id,
    },
  };
  adminDelete(params).then(response => {
    if (response) {
      this.collection.items.remove(this);
      notifyDone();
    }
  });
}
