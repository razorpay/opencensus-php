import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import { subscriptionId, planId, nextDueOn, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchSubscriptions as fetchAll } from 'merchant/reducers/subscriptions';
import SubscriptionsListFilter from 'merchant/views/Subscriptions/Subscriptions/components/ListFilter';

const SubscriptionsTable = (props) => {
  const paymentColumns = [subscriptionId, planId, nextDueOn, createdAtShort, status];

  return <EntityTable title="Subscriptions" columns={paymentColumns} {...props} />;
};

class SubscriptionsList extends ListContainer {
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
        <div className="stats">
          <div className="info">
            <b className="bold">Subscriptions</b>
            {this.statsTable.map((st, ix) => (
              <div key={ix}>
                {st.title}
                <b className="bold">{st.value}</b>
              </div>
            ))}
          </div>
        </div>

        <div className="content-wrapper">
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

export default connect((state) => state.subscriptions, { fetchAll })(withRouter(SubscriptionsList));
