import React, { Component } from 'react';
import Form from 'ui/Form';
import { PageTable } from 'ui/Table';
import { SelectField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

const defaultFilters = {
  status: 'pending',
};

export default class PlanList extends Component {
  collection = new Collection({
    data: {
      route_name: 'onboarding_features_fetch_submissions',
    },
    fetchFn: adminFetch,
    filters: defaultFilters,
  });

  filter = e => this.collection.setFilters({ status: e.target.value });

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>Public Features</header>
          <div class="filters">
            <SelectField
              label="Status"
              name="status"
              onChange={this.filter}
              defaultValue={defaultFilters.status}
            >
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </SelectField>
          </div>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [['Merchant ID', item => item.merchant_id]];
