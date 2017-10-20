import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { showEntity } from './Entity';
import { bool } from 'ui/Item';

@observer
export default class WorkflowList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'admin_get_multiple',
    },
  });

  onSubmit = filters => this.collection.setFilters(filters);
  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Users
            <div class="btn" onClick={this.showEntity}>
              Add User
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <Table model={this.collection} fields={fields} onClick={showEntity} />
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
  ['Action', item => <div class="link danger">Delete</div>],
];
