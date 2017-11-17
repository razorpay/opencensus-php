import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { formatDate } from 'util/index';
import { isSuperAdmin } from 'admin/user';

@observer
export default class RequestList extends Component {
  state = {
    selectedType: 'checker-requested',
  };

  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'workflow_action_get_multiple',
    },
    filters: {
      duty: 'checker',
      type: 'requested',
    },
  });

  onSubmit = filters => this.collection.setFilters(filters);

  selectType = e => {
    let value = e.target.value;

    this.setState({ selectedType: value });
    value = value.split('-');
    let filters = {
      duty: value[0],
      type: value[1],
    };

    this.collection.setFilters(filters);
  };

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Workflow Requests</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
            <SelectField
              label="Workflow Request Type"
              value={this.state.selectedType}
              onChange={this.selectType}
            >
              <option value="maker-created">Made by You</option>
              <option value="checker-requested">Awaiting your Approval</option>
              <option value="maker-closed">Closed by You</option>
              <option value="checker-created">Checked by You</option>
              {isSuperAdmin() && (
                <option value="super-open">View all Open Actions</option>
              )}
              {isSuperAdmin() && (
                <option value="super-all">View all Actions</option>
              )}
            </SelectField>
          </Form>
        </div>
        <PageTable model={this.collection} fields={fields} href={href} />
      </div>
    );
  }
}

// Resources
const fields = [
  ['Action', item => item.permission_description],
  ['Title', item => item.title],
  ['Created By', item => item.admin.name],
  ['Created At', item => formatDate(item.created_at)],
  ['State', item => item.state],
];

const href = item => '/requests/' + item.id;
