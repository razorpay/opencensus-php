import { classList, formatDate } from 'common/util';
import { Link } from 'react-router-dom';
import Collection from 'model/collection';
import { razorxFetch } from 'admin/razorx/fetch';
import { observer } from 'mobx-react';
import { PageTable } from 'ui/Table';

import { statusPill } from 'admin/razorx/data';

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

@observer
export default class extends React.Component {
  collection = new Collection({
    fetchFn: fakeFetch, // razorxFetch,
    data: {
      url: '/featureFlags',
    },
  });

  render() {
    return (
      <div class="list-container">
        <PageTable
          model={this.collection}
          fields={experimentFields}
          href={href}
          info={false}
        />
      </div>
    );
  }
}

const href = item => '/razorx/features/' + item.id;

const experimentFields = [
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
