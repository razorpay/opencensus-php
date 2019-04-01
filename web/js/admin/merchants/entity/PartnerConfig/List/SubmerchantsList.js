import { Link } from 'react-router-dom';

import { PageTable } from 'ui/Table';
import { without } from 'rzp/utils/rzp-utils';

export default function SubmerchantsList(props) {
  return (
    <PageTable
      model={props.model}
      animateRow={false}
      fields={getFields({
        write: props.onWriteConfig,
        defaultConfig: getDefaultConfig(props.defaultConfigs),
      })}
      info={false}
    />
  );
}

function getDefaultConfig(configs = []) {
  return without(configs[0] || {}, 'id');
}

function getFields({ write, defaultConfig }) {
  return [
    [
      'Submerchant ID',
      item => (
        <Link
          to={`/merchants/${item.submerchant.id.replace('acc_', '')}`}
          class="link"
        >
          {item.submerchant.id}
        </Link>
      ),
    ],
    ['Submerchant Name', item => item.submerchant.name],
    [
      'Config',
      item => (
        <button
          class="button"
          onClick={write({ ...item, config: item.config || defaultConfig })}
        >
          {item.config ? 'Update' : 'Override'}
        </button>
      ),
    ],
  ];
}
