import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import DataTable from 'common/ui/Table/DataTable';
import Amount from 'common/ui/Amount';
import { getPaymentSplitAmongstItems } from 'merchant/views/PaymentPages/PaymentPages/model';
import { compose } from 'redux';

const name = { title: 'Item Name', value: (item) => item.name };
const revenue = {
  title: 'Revenue',
  value: (item) => <Amount value={item.net_amount} currency={item.currency} />,
};
const price = {
  title: 'Price',
  value: (item) => <Amount value={item.amount} currency={item.currency} />,
};
const unitsSold = { title: 'Units Sold', value: (item) => item.quantity || '--' };

class PaymentSplitInItems extends Component {
  state = {
    isLoading: false,
    items: [],
  };

  componentDidMount() {
    if (this.isSectionAllowed()) {
      // eslint-disable-next-line react/no-did-mount-set-state
      this.setState({
        isLoading: true,
      });

      getPaymentSplitAmongstItems(this.props.payment.order_id).then((res) => {
        this.setState({
          isLoading: false,
          items: res.data ? res.data.items : [],
        });
      });
    }
  }

  // eslint-disable-next-line consistent-return
  isSectionAllowed() {
    let hash = this.props.location.hash;

    if (hash) {
      hash = hash.substring(1);
      const allowedModules = ['paymentpages', 'paymentbuttons', 'subscription_buttons', 'stores'];

      return allowedModules.indexOf(hash) > -1;
    }
  }

  render() {
    const { isLoading, items } = this.state;

    if (!this.isSectionAllowed()) {
      return null;
    }

    return (
      <EntityDetailRow label="Payment Split">
        <div class="full-width-item sub-entity-list" style={{ marginTop: 24 }}>
          <DataTable
            title="Payments"
            loading={isLoading}
            progressLoader={true}
            columns={[name, revenue, price, unitsSold]}
            items={items}
          />
        </div>
      </EntityDetailRow>
    );
  }
}

export default compose(
  withRouter,
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('PaymentSplitInItems')),
)(PaymentSplitInItems);
