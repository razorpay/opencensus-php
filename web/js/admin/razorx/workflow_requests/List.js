import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import Field, { SelectField, SearchableSelectField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { observer } from 'mobx-react';
import { formatDate } from 'common/util';
import { isSuperAdmin } from 'admin/user';

@observer
export default class WorkflowRequestsList extends Component {
  state = {
    selectedType: 'checker-requested',
    admins: null,
  };

  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      url: 'live/w-actions',
    },
    filters: {
      duty: 'checker',
      type: 'requested',
    },
  });

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

  componentWillMount() {
    let requests = ['live/admins'];

    Promise.all(requests.map(url => adminFetch(url))).then(([admins]) => {
      this.setState({
        admins: admins.items,
      });
    });
  }

  render() {
    const { selectedType, admins } = this.state;

    return (
      <div class="parent-container workflow_requests-container">
        <div class="header">
          <span class="title">Workflows List</span>
        </div>
        <div class="container-group">
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
            {admins &&
              selectedType !== 'maker-created' && (
                <SearchableSelectField
                  label="Maker Id"
                  name="maker_id"
                  placeholder="Search"
                  options={admins.map(admin => ({
                    name: admin.name,
                    value: admin.id.replace('admin_', ''),
                  }))}
                />
              )}

            <input
              name="workflow_id"
              value="Fixed value for RazorX workflow requests"
              class="hide"
              readOnly
            />

            <button class="btn btn--primary">Search</button>
          </Form>
          <div class="list-container">
            <div>
              <PageTable
                model={this.collection}
                fields={fields}
                href={href}
                info={false}
              />
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
