import { connect } from 'react-redux';
import { NavLink, Link } from 'react-router-dom';
import rTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import { getTime } from 'common/ui/item';
import { amount, receipt, status, createdAt as createdAtProperty } from 'common/ui/item/pair';
import CopyLink from 'merchant/components/CopyLink';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchRegistrationLinks as fetchAll } from 'merchant/reducers/collection';
import analytics from 'merchant/views/Subscriptions/analytics';
import { trackSearchEvent } from 'merchant/views/Subscriptions/utils';

import RegistrationLinksListFilter from './components/ListFilter';
import { compose } from 'redux';

const id = {
  title: 'Link ID',
  value: (item) => <Link to={`/registration_links/${item.id}`}>{item.id}</Link>,
};

const link = {
  title: 'Registration Link',
  value: (item) => <CopyLink url={item.short_url} />,
};

const customer = {
  title: 'Customer',
  value: ({ customer_details = {} }) => [
    <div key="email">Email: {customer_details.email}</div>,
    <div key="contact">Contact: {customer_details.contact}</div>,
  ],
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};

class RegistrationLinksListContainer extends ListContainer {
  trackSearch = (event, options) => {
    trackSearchEvent(event, { options, eventStartLabel: 'registrationlink.search' });
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
            <span className="cta-container">
              <NavLink className="btn btn-primary" to="/registration_links/new">
                <i className="i i-plus" />
                <span>Create New Link</span>
              </NavLink>
            </span>
          </div>
        </HeaderAction>

        <RegistrationLinksListFilter
          form="authLinksListFilter"
          count={this.state.count}
          onSubmit={this.onSubmit}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Registration Links"
          skip={this.state.skip}
          columns={[id, amount, receipt, link, customer, createdAt, status]}
          {...this.props}
          count={this.state.count}
          paginate={(params, type) => {
            analytics.track(`registrationlink.browse.${type}`, {
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
  connect((state) => state.registrationLinks, { fetchAll }),
  rTracking(() => window.rzpQ.component('RegistrationLinksList')),
)(RegistrationLinksListContainer);
