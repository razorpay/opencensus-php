import { connect } from 'react-redux';
import { NavLink, Link } from 'react-router-dom';

import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import {
  amount,
  receipt,
  status,
  createdAt as createdAtProperty,
} from 'common/ui/item/pair';

import { fetchRegistrationLinks as fetchAll } from 'merchant/reducers/collection';

import { getTime } from 'common/ui/item';
import CopyLink from 'merchant/components/CopyLink';

import ListContainer from 'merchant/containers/ListContainer';

import ListFilter from './ListFilter';

const id = {
  title: 'Link ID',
  value: item => <Link to={'/registration_links/' + item.id}>{item.id}</Link>,
};

const link = {
  title: 'Registration Link',
  value: item => <CopyLink url={item.short_url} />,
};

const customer = {
  title: 'Customer',
  value: ({ customer_details = {} }) => [
    <div>Email: {customer_details.email}</div>,
    <div>Contact: {customer_details.contact}</div>,
  ],
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};

@connect(state => state.registrationLinks, { fetchAll })
export default class RegistrationLinksList extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <NavLink class="btn btn-primary" to="/registration_links/new">
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
          title="Registration Links"
          skip={this.state.skip}
          paginate={this.paginate}
          columns={[id, amount, receipt, link, customer, createdAt, status]}
          {...this.props}
          count={this.state.count}
        />
      </div>
    );
  }
}
