import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { PaymentStatusLabel, SettlementStatusLabel } from 'merchant/components/StatusLabel';

const commonMeta = {
  columns: [
    {
      recordKey: 'amount',
      transfomer: (value, record, tabName, displayCompact) => {
        const component = <Amount value={value} currency={record.currency} />;

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
          <Link to={{ pathname: `/${tabName}/${value}`, state: { fromHomePage: true } }}>
            <code>{value}</code>
          </Link>
        );
      },
    },
    {
      recordKey: 'created_at',
      transfomer: (value) => <Time value={value} relative />,
    },
    {
      recordKey: 'status',
      transfomer: (value, entity) => {
        const Label = entity.entity === 'settlement' ? SettlementStatusLabel : PaymentStatusLabel;
        value = value || 'refunded';

        return <Label status={value} />;
      },
    },
  ],
};

const tabs = ['payments', 'settlements', 'refunds'];
const tabsMeta = {};

tabs.forEach((tabName) => {
  tabsMeta[tabName] = tabsMeta[tabName] || Object.create(commonMeta);
});

export { tabs };
export { tabsMeta };
