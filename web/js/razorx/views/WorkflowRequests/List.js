import React, { Component } from 'react';
import Form from 'razorx/components/ui/Form';
import { PageTable } from 'razorx/components/ui/Table';
import Field, {
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';
import Collection from 'razorx/model/collection';
import { adminGet } from 'razorx/helpers/admin-fetch';
import { observer } from 'mobx-react';
import { formatDate } from 'razorx/helpers/utils';
import { isSuperAdmin } from 'razorx/user';

@observer
export default class WorkflowRequestsList extends Component {
  state = {
    selectedType: 'checker-requested',
    workflows: null,
  };

  UNSAFE_componentWillMount() {
    return adminGet('live/workflows?count=100').then(data => {
      const workflows = data.items.filter(
        i => i.name.toLowerCase().indexOf('razorx') > -1
      );

      this.defaultWorkflowId = workflows[0].id.replace('workflow_', '');

      this.collection = new Collection({
        fetchFn: adminGet,
        data: {
          url: 'live/w-actions',
        },
        filters: {
          duty: 'checker',
          type: 'requested',
          workflow_id: this.defaultWorkflowId,
        },
      });

      this.setState({ workflows });
    });
  }

  onSubmit = filters => {
    let selectedType = this.state.selectedType;

    selectedType = selectedType.split('-');
    filters = { ...filters, duty: selectedType[0], type: selectedType[1] };

    this.collection.applyFilters(filters);
  };

  selectType = e => {
    let value = e.target.value;

    this.setState({ selectedType: value });
  };

  render() {
    const { selectedType, workflows } = this.state;

    return (
      <div class="parent-container workflow_requests-container">
        <div class="header">
          <span class="title">Workflow Requests</span>
        </div>
        <div class="container-group">
          <div class="list-container">
            <Form onSubmit={this.onSubmit} class="filters">
              <Field
                label="Search Entity Id"
                onChange={this.selectId}
                name="entity_id"
              />
              <SelectField
                label="Request Type"
                value={selectedType}
                onChange={this.selectType}
              >
                <option value="maker-created">Made by You</option>
                <option value="checker-requested">
                  Awaiting your Approval
                </option>
                <option value="maker-closed">Closed by You</option>
                <option value="checker-created">Checked by You</option>
                {isSuperAdmin() && (
                  <option value="super-open">View all Open Actions</option>
                )}
                {isSuperAdmin() && (
                  <option value="super-all">View all Actions</option>
                )}
              </SelectField>

              {workflows && (
                <SearchableSelectField
                  label="Workflow Type"
                  name="workflow_id"
                  placeholder="Search"
                  trackBy="value"
                  defaultValue={this.defaultWorkflowId}
                  options={workflows.map(workflow => ({
                    name: workflow.name,
                    value: workflow.id.replace('workflow_', ''),
                  }))}
                  isSearchable={false}
                  allowClear={false}
                />
              )}

              <button class="btn btn--primary field">Search</button>
            </Form>

            <div>
              {this.collection ? (
                <PageTable
                  model={this.collection}
                  fields={fields}
                  href={href}
                  info={false}
                />
              ) : (
                <div class="table-pending" />
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

// Resources
const fields = [
  ['Action', item => item.permission_description],
  ['Title', item => item.title],
  [
    'Created By',
    item =>
      item.maker
        ? item.maker.name +
          (item.maker_type ? ' (' + item.maker_type + ')' : '')
        : '--',
  ],
  ['Created At', item => formatDate(item.created_at)],
  [
    'State',
    item => <span class={`pill ${item.state}-state`}>{item.state}</span>,
  ],
];

const href = item => '/requests/' + item.id;
