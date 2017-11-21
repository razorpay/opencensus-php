import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { Link } from 'react-router-dom';
import { formatDate } from 'util/index';

@observer
export default class WorkflowList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    deleteRouteName: 'workflow_delete',
    data: {
      route_name: 'workflow_get_multiple',
    },
  });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Workflows
            <Link class="btn pull-right" to={'/workflows/new'}>
              Add New Workflow
            </Link>
          </header>
        </div>
        <PageTable
          model={this.collection}
          fields={getWorkflowListfields(this.collection.delete)}
          href={href}
        />
      </div>
    );
  }
}

const getWorkflowListfields = deleteWorkflow => [
  ['Id', item => item.id],
  ['Name', item => item.name],
  ['Created At', item => formatDate(item.created_at)],
  ['Last Updated', item => formatDate(item.updated_at)],
  [
    'Delete',
    item => (
      <div
        class="link danger"
        onClick={e => {
          e.preventDefault();
          deleteWorkflow(item);
        }}
      >
        Delete
      </div>
    ),
  ],
];

const href = item => '/workflows/' + item.id;
