import React, { Component } from 'react';

import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';

export default class FieldMaps extends Component {
  collection = new Collection({
    data: {
      route_name: 'org_fieldmap_get_multiple',
      url_params: {
        orgId: this.match.params.orgId,
      },
    },
    fetchFn: adminFetch,
  });

  onSubmit = filters => this.collection.setFilters(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Field Maps</header>
          <div class="btn">Add a Field Map</div>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <Table model={this.collection} fields={fields} />
      </div>
    );
  }
}
