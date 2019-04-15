import { Link } from 'react-router-dom';

import { PageTable } from 'ui/Table';
import { without } from 'rzp/utils/rzp-utils';

export default function SubmerchantsList(props) {
  const onSuccess = (...args) => {
    props.model.update(...args);
  };

  return (
    <PageTable
      model={props.model}
      animateRow={false}
      fields={getFields({
        write: props.onWriteConfig,
        defaultConfig: getDefaultConfig(props.defaultConfigs),
        onSuccess: onSuccess,
      })}
      info={false}
    />
  );
}

function getDefaultConfig(configs = []) {
  return without(configs[0] || {}, 'id');
}

function getFields({ write, defaultConfig, onSuccess }) {
  return [
    [
      'Submerchant ID',
      item => {
        const merchantId = item.submerchant.id.replace('acc_', '');
        return (
          <Link to={`/merchants/${merchantId}`} class="link" target="_blank">
            {merchantId}
          </Link>
        );
      },
    ],
    ['Submerchant Name', item => item.submerchant.name],
    ['Submerchant Email', item => item.submerchant.email],
    [
      'Commission Settings',
      item => (
        <button
          class="button"
          onClick={write({
            ...item,
            config: item.config || defaultConfig,
            onSuccess,
          })}
        >
          {item.config ? 'Update' : 'Override'}
        </button>
      ),
    ],
  ];
}
