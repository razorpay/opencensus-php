import { connect } from 'react-redux';
import { NavLink, Link } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';

import { fetchAuthLinks as fetchAll } from 'merchant/modules/collection';

import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';
import { amount, receipt, status } from 'rzp/ui/item/pair';
import CopyLink from 'merchant/components/Invoices/CopyLink';

import ListFilter from './ListFilter';

const customerDetail = prop => item => item.customer_details[prop] || '--';

const id = {
  title: 'Link ID',
  value: item => <Link to={'/authlinks/' + item.id}>{item.id}</Link>,
};

const customerEmail = {
  title: 'Email',
  value: customerDetail('email'),
};

const customerContact = {
  title: 'Contact',
  value: customerDetail('contact'),
};

const link = {
  title: 'Authorization Link',
  value: item => <CopyLink url={item.short_url} />,
};

@connect(
  state => ({
    ...state.authLinks,
  }),
  { fetchAll }
)
export default class AuthLinksList extends ListContainer {
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
          title="Authorization Links"
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
