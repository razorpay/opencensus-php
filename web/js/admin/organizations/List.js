import React, { Component } from 'react';
import { observer } from 'mobx-react';

import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { showEntity } from './Entity';

const fields = [
  ['Organization ID', item => item.id],
  ['Business Name', item => item.business_name],
  ['Display Name', item => item.display_name],
  ['Email', item => item.email],
  ['Email Domain', item => item.email_domains.join(',')],
  ['Actions', item => <Actions id={item.id} />],
];

const Actions = item => (
  <div>
    <div class="link">Edit</div>
  </div>
);

@observer
class OrganizationsList extends Component {
  collection = new Collection({
    data: {
      route_name: 'org_get_multiple',
    },
    fetchFn: adminFetch,
  });

  onSubmit = filters => this.collection.setFilters(filters);

  showEntity = showEntity.bind(null, this.collection);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Organizations</header>
          <div class="btn" onClick={this.showEntity}>
            Add an Organization
          </div>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <Table model={this.collection} fields={fields} onClick={showEntity} />
      </div>
    );
  }
}

export default OrganizationsList;
