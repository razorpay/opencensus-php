import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import PaymentLinksList from 'merchant/containers/PaymentLinks/Links/List';
import BatchUploadList from 'merchant/containers/PaymentLinks/BatchUpload/List';
import PaymentLinksReusable from 'merchant/containers/PaymentLinks/ReusableLinks/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
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
        <ShowWhen myRole="owner manager operations admin">
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
        </ShowWhen>

        <header id="link-header">
          <NavLink exact to="/paymentlinks">
            Payment Links
          </NavLink>
          <NavLink exact to="/paymentlinks/reusable">
            Reusable Links
          </NavLink>
          <ShowWhen myRole="owner manager operations admin">
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
              {this.state.showAnnouncementBanner && (
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

        <TestModeBanner />

        <content>
          <Switch>
            <Route
              path="/paymentlinks/batchuploads"
              component={BatchUploadList}
            />

            <Route
              path="/paymentlinks/reusable"
              component={PaymentLinksReusable}
            />
            <Route path="/paymentlinks" component={PaymentLinksList} />
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
