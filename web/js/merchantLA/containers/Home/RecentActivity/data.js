import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
// TODO: Transfers has not statusLabel, so see what to show here
import {
  PaymentStatusLabel,
  SettlementStatusLabel,
} from 'merchant/components/StatusLabel';

const commonMeta = {
  columns: [
    {
      recordKey: 'amount',
      transfomer: (value, record, tabName, displayCompact) => {
        const component = <Amount value={value} />;

        return displayCompact ? (
          <Link to={`/${tabName}/${record.id}`}>{component}</Link>
        ) : (
          component
        );
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
        let Label =
          entity.entity === 'settlement'
            ? SettlementStatusLabel
            : PaymentStatusLabel;
        value = value || 'refunded';

        return <Label status={value} />;
      },
    },
  ],
};

const tabs = ['transfers', 'settlements', 'reversals'],
  tabsMeta = {};

tabs.forEach(tabName => {
  tabsMeta[tabName] = tabsMeta[tabName] || Object.create(commonMeta);
});

export { tabs };
export { tabsMeta };
