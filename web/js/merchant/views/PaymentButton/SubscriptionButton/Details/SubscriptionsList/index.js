import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchSubscriptions as fetchAll } from 'merchant/reducers/subscriptions';

import ListContainer from 'merchant/containers/ListContainer';
import SubscriptionsListFilter from 'merchant/views/Subscriptions/Subscriptions/components/ListFilter';

import { subscriptionId, planId, nextDueOn, createdAtShort, status } from 'common/ui/item/pair';

import EntityTable from 'merchant/components/EntityTable';

const SubscriptionsTable = (props) => {
  const paymentColumns = [subscriptionId, planId, nextDueOn, createdAtShort, status];

  return <EntityTable title="Subscriptions" columns={paymentColumns} {...props} />;
};

@withRouter
@connect((state) => state.subscriptions, { fetchAll })
export default class SubscriptionsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    return this.props.fetchAll({
      ...params,
      source: 'subscription_button',
      source_id: this.props.entity.id.substr(3),
    });
  };

  get statsTable() {
    const { entity } = this.props;
    const items = entity.payment_page_items;

    const totalSubscriptions = items.reduce((total, item) => {
      if (item.plan_id) {
        total += Number(item.quantity_sold);
      }

      return total;
    }, 0);

    return [
      {
        title: 'Total Subscriptions',
        value: totalSubscriptions,
      },
    ];
  }

  render() {
    const { children, entity, ...restProps } = this.props;

    return (
      <div>
        <div class="stats">
          <div class="info">
            <b class="bold">Subscriptions</b>
            {this.statsTable.map((st, ix) => (
              <div key={ix}>
                {st.title}
                <b class="bold">{st.value}</b>
              </div>
            ))}
          </div>
        </div>

        <div class="content-wrapper">
          {children}

          <SubscriptionsListFilter
            key="subscriptions"
            form="subscriptionsListFilter"
            count={this.state.count}
            onSubmit={this.search}
            fetchAll={this.fetchAll}
          />
          <SubscriptionsTable
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
            {...restProps}
          />
        </div>
      </div>
    );
  }
}
