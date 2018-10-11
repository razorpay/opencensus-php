import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';

import { fetchInvoices } from 'merchant/modules/invoices/list';

import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';
import { authLink as id, amount, receipt, status } from 'rzp/ui/item/pair';
import CopyLink from 'merchant/components/Invoices/CopyLink';

import ListFilter from './ListFilter';

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
    items: state.invoices.invoices,
  }),
  { fetchInvoices }
)
export default class AuthLinksList extends ListContainer {
  fetchEntityList(params) {
    params.type = 'auth_link';
    return this.props.fetchInvoices(params);
  }

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <NavLink class="btn btn-primary" to="/authlinks/new">
              <i class="i i-plus" />
              <span>Create New Link</span>
            </NavLink>
          </div>
        </HeaderAction>

        <ListFilter
          form="authLinksListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Auth Links"
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
          {...this.props}
          count={this.state.count}
        />
      </div>
    );
  }
}
