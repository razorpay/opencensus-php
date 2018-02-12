import { getType } from 'rzp/utils/entity';
import { humanize } from 'rzp/utils/rzp-utils';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import EntityDetailRow from './EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { NavLink } from 'react-router-dom';

const entityWithViews = [
  // 'bank_account',
  // 'customer',
  'invoice',
  'payment',
  'refund',
  'settlement',
  'order',
  // 'offer'
];

export default ({ label, value, entity = {} }) => {
  let type = getType(label, value);
  let currency = entity.currency || 'INR';
  let val = value;
  let entityName;

  switch (type) {
    case 'timestamp':
      val = () => <Time value={value} format="DD MMM YYYY, hh:mm:ss a" />;
      break;

    case 'amount_inr':
      val = () => <Amount value={value} />;
      break;

    case 'amount':
      val = () => <Amount value={value} currency={currency} />;
      break;

    case 'id':
      entityName = label.split('_')[0];
      let url = `/${entityName}s/${value}`;

      if (entityName === 'invoice') {
        url += '/details';
      }

      val = () => {
        if (entityWithViews.indexOf(entityName) > -1) {
          return <NavLink to={url}>{value}</NavLink>;
        }

        return value;
      };
  }

  label = typeof label === 'function' ? label : humanize(label);

  if (typeof val === 'object') {
    return <NestedEntityDetailRow label={label} value={val} />;
  }

  return <EntityDetailRow label={label} value={val} />;
};
