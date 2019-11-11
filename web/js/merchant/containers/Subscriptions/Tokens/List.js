import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';

import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import DocsLink from 'merchant/components/DocsLink';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';

import { titleCase } from 'common/util';
import { fetchTokens as fetchAll } from 'merchant/reducers/collection';

import { tokenId, createdAt } from 'rzp/ui/item/pair';

import ListFilter from './ListFilter';

const method = {
  title: 'Method',
  value: item => titleCase(item.method),
};

const email = {
  title: 'Email',
  value: item => (item.customer ? item.customer.email : '--'),
};

const contact = {
  title: 'Contact',
  value: item => (item.customer ? item.customer.contact : '--'),
};

const status = {
  title: 'Status',
  value: item => <TokenStatusLabel status={getTokenStatus(item)} />,
};

@connect(
  state => ({
    ...state.tokens,
  }),
  { fetchAll }
)
export default class TokensList extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink
              title="Documentation"
              url="https://razorpay.com/docs/recurring-payments/token/"
            />
          </div>
        </HeaderAction>

        <ListFilter
          form="tokensListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Tokens"
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          columns={[tokenId, method, email, contact, createdAt, status]}
          {...this.props}
        />
      </div>
    );
  }
}

export function getTokenStatus(token) {
  return token.method === 'card' ? 'confirmed' : token.recurring_details.status;
}
