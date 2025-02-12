import { classList } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { Link } from 'react-router-dom';
import Collection from 'razorx/model/collection';
import { rexFetch } from 'razorx/helpers/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'razorx/components/ui/Table';

import { statusPill } from 'razorx/helpers/data';

import Form from 'razorx/components/ui/Form';
import Field, { DateField } from 'razorx/components/ui/Field';

import { AppStore } from 'razorx/store';

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: rexFetch,
    data: {
      url: 'feature_flags',
    },
  });

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
    const { params = {} } = this.props;

    return (
      <div className="list-container">
        <Form onSubmit={this.applyFilters} className="filters">
          <Field label="Id" name="id" defaultValue={params.id || ''} />
          <Field label="Name" name="name" />
          <Field label="Created By" name="created_by" placeholder="Email" />

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
            fields={featuresFields}
            href={href}
            info={false}
          />
        </div>
      </div>
    );
  }
}

const href = item => '/features_flags/' + item.id;

const featuresFields = [
  ['ID', item => item.id],
  [
    'Name',
    item => (
      <span className={classList(item.active_experiments > 0 && 'is-active')}>
        {item.name}
      </span>
    ),
  ],
  ['Description', item => item.description],
  // ['Active Experiments', item => item.active_experiments],
  ['Total Variants', item => item.variants.length],
  ['Created On', item => formatDate(item.created_at)],
];
