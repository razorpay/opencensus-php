import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import RTracking from 'react-tracking';

import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import DocsLink from 'merchant/components/DocsLink';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';

import { titleCase } from 'common/utils/rzp-utils';
import { fetchTokens as fetchAll } from 'merchant/reducers/collection';

import { tokenId, createdAt } from 'common/ui/item/pair';

import ListFilter from './components/ListFilter';
import analytics from '../analytics';
import { trackSearchEvent } from '../utils';

const method = {
  title: 'Method',
  value: (item) => {
    if (['upi', 'nach'].includes(item.method)) return item.method.toUpperCase();
    return titleCase(item.method);
  },
};

const email = {
  title: 'Email',
  value: (item) => (item.customer ? item.customer.email : '--'),
};

const contact = {
  title: 'Contact',
  value: (item) => (item.customer ? item.customer.contact : '--'),
};

const status = {
  title: 'Status',
  value: (item) => <TokenStatusLabel status={getTokenStatus(item)} />,
};

@connect(
  (state) => ({
    ...state.tokens,
  }),
  { fetchAll },
)
@RTracking(() => window.rzpQ.component('TokensList'))
export default class TokensList extends ListContainer {
  trackSearch = (event, options) => {
    trackSearchEvent(event, { options, eventStartLabel: 'token.search' });
  };

  onSubmit = (filters) => {
    this.trackSearch('initiate');
    this.trackSearch(filters);
    this.search(filters);
  };

  onClearAnalytics = () => {
    this.trackSearch('clear');
  };

  onErrorCloseClick = () => {
    this.trackSearch('error', { response: this.state.status.message[1] });
  };

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction responsive>
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
          onSubmit={this.onSubmit}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Tokens"
          count={this.state.count}
          skip={this.state.skip}
          columns={[tokenId, method, email, contact, createdAt, status]}
          {...this.props}
          paginate={(params, type) => {
            analytics.track(`token.browse.${type}`, {
              page: params.skip % params.count,
            });

            this.paginate(params);
          }}
          onErrorCloseClick={this.onErrorCloseClick}
        />
      </div>
    );
  }
}

export function getTokenStatus(token) {
  return token.method === 'card' ? 'confirmed' : token.recurring_details.status;
}
