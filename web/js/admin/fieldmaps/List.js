import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'common/fetch';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';

import { showEntity, removeEntity } from './Entity';

function openEntity(collection) {
  return function(e) {
    showEntity.call(this, collection);
  };
}

const fields = [
  ['ID', item => item.id],
  ['Org ID', item => item.org_id],
  ['Entity Name', item => item.entity_name],
  ['Fields', item => item.fields.join(', ')],
  [
    'Actions',
    item => (
      <div class="link danger" onClick={item::removeEntity}>
        Delete
      </div>
    ),
  ],
];

@observer
export default class FieldMaps extends Component {
  collection = new Collection({
    data: {
      url: `live/field-map`,
    },
    fetchFn: adminFetch,
  });

  onSubmit = filters => this.collection.applyFilters(filters);

  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Field Maps
            <div class="btn pull-right" onClick={openEntity(this.collection)}>
              Add a Field Map
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <PageTable
          model={this.collection}
          fields={fields}
          onClick={openEntity(this.collection)}
        />
      </div>
    );
  }
}
