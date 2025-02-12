import { connect } from 'react-redux';
import rTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import { tokenId, createdAt } from 'common/ui/item/pair';
import { titleCase } from 'common/utils/rzp-utils';
import DocsLink from 'merchant/components/DocsLink';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchTokens as fetchAll } from 'merchant/reducers/collection';
import analytics from 'merchant/views/Subscriptions/analytics';
import { trackSearchEvent } from 'merchant/views/Subscriptions/utils';

import TokensListFilter from './components/ListFilter';
import { compose } from 'redux';

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

class TokensListContainer extends ListContainer {
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
      <div className="content-wrapper">
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right">
            <DocsLink
              title="Documentation"
              url="https://razorpay.com/docs/recurring-payments/token/"
            />
          </div>
        </HeaderAction>

        <TokensListFilter
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

export default compose(
  withRouter,
  connect(
    (state) => ({
      ...state.tokens,
    }),
    { fetchAll },
  ),
  rTracking(() => window.rzpQ.component('TokensList')),
)(TokensListContainer);

export function getTokenStatus(token) {
  return token?.recurring_details?.status ?? '';
}
