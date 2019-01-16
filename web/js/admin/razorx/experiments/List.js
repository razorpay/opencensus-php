import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { razorxFetch } from 'admin/razorx/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';
import { formatDate } from 'common/util';

import Form from 'ui/Form';
import Field from 'ui/Field';

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

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: fakeFetch, // razorxFetch,
    data: {
      url: '/experiments',
    },
  });

  onSubmit = filters => {
    let selectedType = this.state.selectedType;

    selectedType = selectedType.split('-');
    filters = { ...filters, duty: selectedType[0], type: selectedType[1] };

    this.collection.applyFilters(filters);
  };

  render() {
    return (
      <div class="list-container">
        <Form onSubmit={this.onSubmit} class="filters">
          <Field label="Created By" name="created_by" />

          <button class="btn btn--primary field">Search</button>
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

const href = item => '/razorx/experiments/' + item.id;

const experimentFields = [
  ['Description', item => item.description],
  [
    'Feature',
    item => (
      <object>
        <Link to={`/razorx/features/${item.feature_id}`}>
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
