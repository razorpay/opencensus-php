import { Link } from 'react-router-dom';
import TimeAgo from 'react-timeago';

import Amount from 'rzp/ui/Amount';
import {
  PaymentStatusLabel,
  SettlementStatusLabel,
} from 'merchant/components/StatusLabel';

const commonMeta = {
  numColumns: 4,
  columns: [
    {
      recordKey: 'amount',
      transfomer: value => {
        return <Amount value={value} />;
      },
    },
    {
      recordKey: 'id',
      transfomer: value => {
        return (
          <Link to={`/payments/${value}`}>
            <code>{value}</code>
          </Link>
        );
      },
    },
    {
      recordKey: 'created_at',
      transfomer: value => <TimeAgo date={value * 1000} />,
    },
    {
      recordKey: 'status',
      transfomer: (value, entity) => {

        let Label = entity.entity === 'settlement'
                      ? SettlementStatusLabel
                      : PaymentStatusLabel;
        value = value || 'refunded';

        return <Label status={value} />;
      },
    },
  ],
};

const tabs = ['payments', 'settlements', 'refunds'],
  tabsMeta = {};

tabs.forEach(tabName => {
  tabsMeta[tabName] = tabsMeta[tabName] || Object.create(commonMeta);
});

export { tabs };
export { tabsMeta };
