import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { rexFetch } from 'admin/razorx/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';
import { formatDate } from 'common/util';

import Form from 'ui/Form';
import Field, { DateField, SelectField } from 'ui/Field';

import { statusPill } from 'admin/razorx/data';
import { AppStore } from 'admin/razorx/store';

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: rexFetch,
    data: {
      url: 'experiments',
    },
    filters: this.props.queryParams,
  });

  componentDidUpdate(prevProps) {
    if (prevProps.mode !== this.props.mode) {
      this.collection.fetch(); // Automatically fetches as per current mode
    } else {
      const lastQP = Object.keys(prevProps.queryParams);
      const curQP = Object.keys(this.props.queryParams);

      let isDiff = false;
      for (let i = 0; i < lastQP.length; i++) {
        if (lastQP[i] !== curQP[i]) {
          isDiff = true;
          break;
        }
      }

      if (isDiff) {
        this.collection.applyFilters(this.props.queryParams);
      }
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
    const { queryParams = {} } = this.props;

    return (
      <div class="list-container">
        <Form onSubmit={this.applyFilters} class="filters">
          <Field
            label="Feature Id"
            name="feature_id"
            defaultValue={queryParams.feature_id}
          />
          <Field label="Created By" name="created_by" />

          <SelectField
            name="status"
            label="Status"
            defaultValue={queryParams.status}
          >
            <option value="">All</option>
            <option value="created">Created</option>
            <option value="terminated">Terminated</option>
            <option value="activated">Activated</option>
          </SelectField>

          <SelectField name="environment" label="Environment">
            <option value="">All</option>
            {AppStore.environmentList.map((e, i) => (
              <option key={i} value={e}>
                {e}
              </option>
            ))}
          </SelectField>

          <DateField
            label="Activated On"
            name="activated_at"
            format="X"
            placeholder="Unix (1549737000)"
            postSelectionValue={val => val.startOf('day')}
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
  ['ID', item => item.id],
  ['Description', item => item.description],
  [
    'Feature',
    item => (
      <object>
        <Link to={`/features_flags/${item.feature_id}`}>
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
