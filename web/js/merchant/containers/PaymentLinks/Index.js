import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import LinkList from 'merchant/containers/PaymentLinks/List';
import BatchList from 'merchant/containers/PaymentLinks/BatchList';
import BatchListNew from 'merchant/containers/PaymentLinks/BatchListNew';

import Button from 'component/Button';

import LocalStorageService from 'rzp/utils/localStorage';
import { classList } from 'common/util';
import createEvent from 'rzp/utils/event';
import {
  trackLinkClick,
  trackAnnouncementShown,
  trackCloseAnnouncement,
} from './ga';

export function toPLBUBannerShown() {
  return !LocalStorageService.getItem('plbu-banner-viewed'); // If the key exists, then already viewed
}

@connect(state => {
  return {
    user: state.session.user,
  };
})
export default class PaymentLinksContainer extends Component {
  state = {
    showAnnouncementBanner: toPLBUBannerShown(),
  };

  handleAnnouncementClose = e => {
    trackCloseAnnouncement();

    // Remove 'new tag' from SideNav->'Payment Links'
    this.setState({
      showAnnouncementBanner: false,
    });
    LocalStorageService.setItem('plbu-banner-viewed', '1'); // Store in local storage.  Value can be anything. Key must exist.

    const event = createEvent('remove_PLBU-Announcement', { bubbles: false });
    window.dispatchEvent(event);
  };

  componentDidMount() {
    trackAnnouncementShown();
  }

  render() {
    const { user } = this.props;

    return (
      <tabbed-container>
        <AnnouncementBanner
          handleClick={this.handleAnnouncementClose}
          hidden={!this.state.showAnnouncementBanner}
          content={
            <span>
              Issuing hundreds of payment links manually? Instead, upload an
              excel sheet and leave the rest to us. Try our{' '}
              <NavLink
                class="link"
                to="/paymentlinks/batchuploads"
                onClick={trackLinkClick}
              >
                Batch Uploads
              </NavLink>.
            </span>
          }
        />

        <header id="link-header">
          <NavLink exact to="/paymentlinks">
            Payment Links
          </NavLink>
          <ShowWhen
            myRole="owner manager operations admin"
            featureEnabled="batch_import_links_v2"
          >
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
              {user.isBatchEnabled &&
                this.state.showAnnouncementBanner && (
                  <span
                    class="badge bg-success hidden-xs"
                    style={{ marginLeft: '5px' }}
                  >
                    new
                  </span>
                )}
            </NavLink>
          </ShowWhen>
        </header>

        <content>
          <Switch>
            {user.isBatchEnabled ? (
              <Route
                path="/paymentlinks/batchuploads"
                component={BatchListNew}
              />
            ) : (
              <Route path="/paymentlinks/batchuploads" component={BatchList} />
            )}

            <Route path="/paymentlinks" component={LinkList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}

const AnnouncementBanner = ({ className, hidden, content, handleClick }) => {
  return (
    <div
      class={classList(
        'Announcement_Banner',
        className,
        hidden && 'Announcement_Banner--hide'
      )}
    >
      {content}
      <Button.Transparent class="close-btn" onClick={handleClick}>
        ×
      </Button.Transparent>
    </div>
  );
};
