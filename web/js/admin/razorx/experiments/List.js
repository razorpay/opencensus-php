import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';
import { formatDate } from 'common/util';

import Form from 'ui/Form';
import Field, { DateField, SelectField } from 'ui/Field';

import { statusPill } from 'admin/razorx/data';

const data = {
  items: [
    {
      id: 201,
      description: 'Random description for this experiment',
      environment: 'testing',
      mode: 'test',
      feature_id: 182,
      segments: [
        {
          variant: 'on',
          type: 'whitelist',
          ids: ['merchant1', 'merchant2'],
          weight: 0,
        },
        {
          variant: 'off',
          type: 'ramp',
          ids: null,
          weight: 8,
        },
      ],
      created_by: 'a@a.com',
      updated_by: '',
      activated_by: 'Quala',
      terminated_by: 'Quala',
      status: 'terminated',
      created_at: 1546434038,
      updated_at: 1546434038,
      activated_at: 1546434062,
      terminated_at: null,
      deleted_at: 0,
    },
    {
      id: 202,
      description: 'Rollout Reports V3 to a small population',
      environment: 'testing',
      mode: 'test',
      feature_id: 183,
      segments: [
        {
          variant: 'on',
          type: 'whitelist',
          ids: ['merchant1', 'merchant2'],
          weight: 0,
        },
        {
          variant: 'off',
          type: 'ramp',
          ids: null,
          weight: 8,
        },
      ],
      created_by: 'a@a.com',
      updated_by: '',
      activated_by: 'Quala',
      terminated_by: 'Quala',
      status: 'activated',
      created_at: 1546434038,
      updated_at: 1546434038,
      activated_at: 1546434062,
      terminated_at: 1546434079,
      deleted_at: 0,
    },
  ],
};

function fakeFetch() {
  return new Promise((resolve, reject) => {
    setTimeout(function() {
      resolve(data);
    }, 2000);
  });
}

const baseUrl = 'experiments';

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      url: `${this.props.mode}/${baseUrl}`,
    },
    extraFields: {
      mode: this.props.mode || 'live',
    },
  });

  componentDidUpdate(prevProps) {
    if (prevProps.mode !== this.props.mode) {
      this.collection.data.url = `${this.props.mode}/${baseUrl}`;
      this.collection.fetch();
    }
  }

  resetFilters = e => {
    const hasAppliedFilters = Object.keys(this.collection.filters).length > 2; // count and skip are by default

    // Clean filters in collection
    this.collection.resetFilters();

    // Clear filters in UI form
    const form = e.currentTarget.closest('form');
    form.reset();

    if (hasAppliedFilters) {
      this.collection.fetch();
    }
  };

  applyFilters = filters => {
    filters = { ...filters };
    this.collection.applyFilters(filters);
  };

  render() {
    return (
      <div class="list-container">
        <Form onSubmit={this.applyFilters} class="filters">
          <Field label="Feature Id" name="feature_id" />
          <Field label="Created By" name="created_by" />

          <SelectField name="status" label="Status">
            <option value="created">Created</option>
            <option value="terminated">Terminated</option>
            <option value="activated">Activated</option>
          </SelectField>

          <SelectField name="environment" label="Environment">
            <option value="production">Production</option>
            <option value="beta">Beta</option>
          </SelectField>

          <DateField
            label="Activated On"
            name="activated_at"
            placeholder="YYYY-MM-DD"
            format="YYYY-MM-DD"
          />

          <button class="btn btn--primary field">Search</button>
          <button
            type="button"
            class="btn btn--link field"
            onClick={this.resetFilters}
          >
            Clear
          </button>
        </Form>
        <div>
          <PageTable
            model={this.collection}
            fields={experimentFields}
            href={href}
            info={false}
          />
        </div>
      </div>
    );
  }
}

const href = item => '/experiments/' + item.id;

const experimentFields = [
  ['Description', item => item.description],
  [
    'Feature',
    item => (
      <object>
        <Link to={`/features/${item.feature_id}`}>
          <span class="link">{item.feature_id}</span>
        </Link>
      </object>
    ),
  ],
  ['Status', item => statusPill(item.status)],
  [
    'Activated On',
    item => (item.activated_at ? formatDate(item.activated_at) : '--'),
  ],
  [
    'Terminated On',
    item => (item.terminated_at ? formatDate(item.terminated_at) : '--'),
  ],
];
