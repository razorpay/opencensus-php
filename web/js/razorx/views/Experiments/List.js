import { Link } from 'react-router-dom';
import Collection from 'razorx/model/collection';
import { rexFetch } from 'razorx/helpers/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'razorx/components/ui/Table';
import { formatDate } from 'razorx/helpers/utils';

import Form from 'razorx/components/ui/Form';
import Field, { DateField, SelectField } from 'razorx/components/ui/Field';

import { statusPill } from 'razorx/helpers/data';
import { AppStore } from 'razorx/store';

@observer
export default class extends React.Component {
  state = { queryParams: this.props.queryParams || {} };

  collection = new Collection({
    fetchFn: rexFetch,
    data: {
      url: 'experiments',
    },
    filters: this.props.queryParams,
  });

  componentWillUpdate(nextProps) {
    if (nextProps.mode !== this.props.mode) {
      this.collection.fetch(); // Automatically fetches as per current mode
    } else {
      const curQP = Object.keys(this.props.queryParams);
      const nextQP = Object.keys(nextProps.queryParams);

      let isDiff = false;
      for (let i = 0; i < curQP.length; i++) {
        if (
          nextProps.queryParams[curQP[i]] !== this.props.queryParams[nextQP[i]]
        ) {
          isDiff = true;
          break;
        }
      }

      if (isDiff) {
        this.collection.applyFilters(nextProps.queryParams);

        this.setState({
          queryParams: nextProps.queryParams,
        });
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
    const { queryParams = {} } = this.state;

    return (
      <div className="list-container">
        <Form onSubmit={this.applyFilters} className="filters">
          <Field
            label="Feature Id"
            name="feature_id"
            value={queryParams.feature_id || ''}
            onChange={e => {
              this.setState({
                queryParams: { ...queryParams, feature_id: e.target.value },
              });
            }}
          />
          <Field label="Created By" name="created_by" />

          <SelectField
            name="status"
            label="Status"
            value={queryParams.status || ''}
            onChange={e => {
              this.setState({
                queryParams: { ...queryParams, status: e.target.value },
              });
            }}
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

          <button className="btn btn--primary field">Search</button>
          <button
            type="button"
            className="btn btn--link field"
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
          <span className="link">{item.feature_id}</span>
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
