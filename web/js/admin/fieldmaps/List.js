import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'util/fetch';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';

import { showEntity, removeEntity } from './Entity';

const fields = [
  ['ID', item => item.id],
  ['Org ID', item => item.org_id],
  ['Entity Name', item => item.entity_name],
  ['Fields', item => item.fields.join(',')],
  [
    'Actions',
    item => {
      return (
        <div>
          <div class="link" onClick={item::showEntity}>
            EDIT
          </div>
          <div class="link danger" onClick={item::removeEntity}>
            DELETE
          </div>
        </div>
      );
    },
  ],
];

@observer
export default class FieldMaps extends Component {
  collection = new Collection({
    data: {
      route_name: 'org_fieldmap_get_multiple',
      url_params: {
        orgId: this.props.match.params.orgId,
      },
    },
    fetchFn: adminFetch,
    model: CollectionItem,
  });

  onSubmit = filters => this.collection.setFilters(filters);

  showEntity = showEntity.bind(null, this.collection);

  render() {
    //Bind org_id for adding new items
    let newCollection = {
      ...this.collection,
      org_id: this.props.match.params.orgId,
    };

    return (
      <div class="list-container">
        <div class="box">
          <header>
            Field Maps
            <div class="btn" onClick={showEntity.bind(newCollection)}>
              Add a Field Map
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}
