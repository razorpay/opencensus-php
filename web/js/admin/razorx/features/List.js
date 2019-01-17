import { classList, formatDate } from 'common/util';
import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';

import { statusPill } from 'admin/razorx/data';

import Form from 'ui/Form';
import Field, { DateField } from 'ui/Field';

import { AppStore } from 'admin/user';

const data = {
  items: [
    {
      id: 182,
      name: 'Reports-V3-migration',
      description: 'Move reports to ES',
      created_by: 'tom',
      active_experiments: 4,
      created_at: 1546434027,
      updated_at: 1546434027,
      deleted_at: 0,
      variants: ['on', 'off'],
    },
    {
      id: 184,
      name: 'Ymmm-V8-migration',
      description: 'Move reports to ES',
      created_by: 'tom',
      active_experiments: 0,
      created_at: 1546434027,
      updated_at: 1546434027,
      deleted_at: 0,
      variants: ['on', 'off'],
    },
    {
      id: 181,
      name: 'Blabla-V0-migration',
      description: 'Move reports to ES',
      created_by: 'tom',
      active_experiments: 8,
      created_at: 1546434027,
      updated_at: 1546434027,
      deleted_at: 0,
      variants: ['on', 'off'],
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

const baseUrl = 'featureFlags';

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: adminFetch, // fakeFetch,
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
          <Field label="Id" name="id" />
          <Field label="Name" name="name" />
          <Field label="Created By" name="created_by" />

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
            fields={featuresFields}
            href={href}
            info={false}
          />
        </div>
      </div>
    );
  }
}

const href = item => '/features/' + item.id;

const featuresFields = [
  [
    'Name',
    item => (
      <span class={classList(item.active_experiments > 0 && 'is-active')}>
        {item.name}
      </span>
    ),
  ],
  ['Description', item => item.description],
  ['Active Experiments', item => item.active_experiments],
  ['Total Variants', item => item.variants.length],
  ['Created On', item => formatDate(item.created_at)],
];
