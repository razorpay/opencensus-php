import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminFetch } from 'util/fetch';
import { PageTable } from 'ui/Table';
import Field from 'ui/Field';
import Form from 'ui/Form';

const fields = [
  ['Action', item => item.permission_description],
  ['Title', item => item.title],
  ['Name', item => item.admin.name],
  ['Create At', item => new Date(item.created_at).toTimeString()],
  ['State', item => item.state],
];

@observer
export default class WorkflowRequests extends Component {
  collection = new Collection({
    data: {
      route_name: 'workflow_action_get_multiple',
    },
    filters: { duty: 'checker', type: 'requested', count: 50, skip: 0 },
    fetchFn: adminFetch,
    model: CollectionItem,
  });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Workflow Requests</header>
          <Form class="filters">
            <Field label="Search" />
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}
