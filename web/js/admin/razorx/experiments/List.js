import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { razorxFetch } from 'admin/razorx/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';
import { formatDate } from 'common/util';

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

  render() {
    return (
      <div class="list-container">
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
