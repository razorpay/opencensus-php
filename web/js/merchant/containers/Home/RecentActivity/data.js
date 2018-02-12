import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
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
      transfomer: (value, record, tabName) => {
        return (
          <Link to={`/${tabName}/${value}`}>
            <code>{value}</code>
          </Link>
        );
      },
    },
    {
      recordKey: 'created_at',
      transfomer: value => <Time value={value} relative />,
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
