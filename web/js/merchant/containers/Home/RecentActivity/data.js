import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { PaymentStatusLabel, SettlementStatusLabel } from 'merchant/components/StatusLabel';
import { SelfServeActionPages } from 'common/constant/enums';

const commonMeta = {
  columns: [
    {
      recordKey: 'amount',
      transfomer: (value, record, tabName, displayCompact, merchantCurrency) => {
        const currency = record.currency || merchantCurrency;
        const component = <Amount value={value} currency={currency} />;

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
        const state = { fromHomePage: true };
        if (tabName === 'refunds') {
          state.openedFrom = SelfServeActionPages.HomeRecentactivity;
        }

        if (['payments', 'settlements', 'refunds'].includes(tabName)) {
          return (
            <Link
              to={`/${tabName}/${value}?init_point=${tabName}-table&init_page=${SelfServeActionPages.HomeRecentactivity}`}
              state={state}
            >
              <code>{value}</code>
            </Link>
          );
        } else {
          return (
            <Link
              to={`/${tabName}/${value}`}
              state={{ fromHomePage: true, openedFrom: SelfServeActionPages.HomeRecentactivity }}
            >
              <code>{value}</code>
            </Link>
          );
        }
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
