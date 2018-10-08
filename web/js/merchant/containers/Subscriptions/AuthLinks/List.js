import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';

import { fetchInvoices } from 'merchant/modules/invoices/list';

import DataTable from 'rzp/ui/Table/DataTable';
import { authLink as id, amount, receipt, status } from 'rzp/ui/item/pair';
import CopyLink from 'merchant/components/Invoices/CopyLink';

const customerDetail = prop => item => item.customer_details[prop] || '--';

const customerEmail = {
  title: 'Email',
  value: customerDetail('email'),
};

const customerContact = {
  title: 'Contact',
  value: customerDetail('contact'),
};

const link = {
  title: 'Auth Link',
  value: item => <CopyLink url={item.short_url} />,
};

@connect(
  state => ({
    ...state.invoices,
  }),
  { fetchInvoices }
)
export default class AuthLinksList extends ListContainer {
  fetchEntityList(params) {
    params.type = 'auth_link';
    return this.props.fetchInvoices(params);
  }

  render() {
    const { invoices } = this.props;
    return (
      <div class="content-wrapper">
        <DataTable
          title="Auth Links"
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          columns={[
            id,
            amount,
            receipt,
            link,
            customerEmail,
            customerContact,
            status,
          ]}
          items={invoices}
          {...this.props}
        />
      </div>
    );
  }
}
