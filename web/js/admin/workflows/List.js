import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { showEntity } from './Entity';

@observer
export default class WorkflowList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'workflow_get_multiple',
    },
  });

  onSubmit = filters => this.collection.setFilters(filters);

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Workflows</header>
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
  ['Created At', item => Date(item.created_at)],
  ['Last Updated', item => Date(item.updated_at)],
];
