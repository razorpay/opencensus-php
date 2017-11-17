import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { showEntity, removeEntity } from './Entity';
import CollectionItem from 'model/collectionItem';

@observer
export default class WorkflowList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'group_get_multiple',
    },
    model: CollectionItem,
  });

  onSubmit = filters => this.collection.setFilters(filters);
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
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
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
