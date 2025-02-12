import React from 'react';
import isObject from 'is-object';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { getNotificationTrackingProperties } from 'common/ui/WhatsNew/common';
import { sendDataToSalesForce } from 'common/utils/common-api';
import debounce from 'common/utils/debounce';
import { classList } from 'common/utils/rzp-utils';
import sanitizer, { customWhiteList } from 'common/utils/xss-sanitizer';
import {
  popSlider as popSliderAsProp,
  emptySliderStack,
} from 'merchant_common/reducers/multiSlider';

const getButtonClass = (type) => {
  switch (type) {
    case 'button':
      return 'btn-primary';
    case 'primary-inverted': {
      return 'btn-primary--invert';
    }
    default: {
      return 'btn-link';
    }
  }
};

const isWhatsNewSection = (id) => {
  return id.includes('whats-new');
};

const getCustomWhiteList = () => {
  const whitelist = isObject(customWhiteList) ? { ...customWhiteList } : {};
  const l2WhiteList = Object.fromEntries(
    Object.entries(whitelist).map(([key, val]) => [
      key,
      Array.isArray(val) ? [...val, 'class'] : ['class'],
    ]),
  );

  return l2WhiteList;
};

class AnnouncementDetails extends React.Component {
  lazy = this.props.lazy || this.props.location?.state?.lazy || false;

  state = {
    hasScrolledToEnd: false,
  };

  getCommonNotificationTrackingProperties() {
    const notification =
      this.props.announcements?.find(
        (notificationEntry) => notificationEntry.id === this.props.id,
      ) || {};

    return getNotificationTrackingProperties(notification);
  }

  componentDidMount() {
    const { id, tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('dashboard.notification_section.card.l2.display', {
        trackingID: id,
        title: this.props.announcements?.find((notification) => notification.id === id)?.l2_content
          ?.title,
        whats_new: isWhatsNewSection(id),
        ...this.getCommonNotificationTrackingProperties(),
      }),
    );
  }

  createSalesforceOpportunity = (id, url) => {
    let event = '';

    switch (id) {
      case 'cash-advance-cta-1': {
        event = 'LOC-Cross-sell-V1';
        break;
      }
      case 'whats-new-JUN21-RXCC-GROWTH-cta1': {
        event = 'capital-whats-new';
        break;
      }
      case 'ultra-campaign-announcement-cta-1': {
        event = 'ultra-campaign';
        break;
      }
      case 'ultra-p2-cash-advance-cta-1': {
        event = 'ultra-campaign-p2-cash-advance';
        break;
      }
      default: {
        break;
      }
    }

    sendDataToSalesForce(event, this.props.user);
    this.props.history.push(url);
  };

  handleCTA = ({ id, url }) => {
    switch (id) {
      case 'cash-advance-cta-1':
      case 'whats-new-JUN21-RXCC-GROWTH-cta1':
      case 'ultra-campaign-announcement-cta-1':
      case 'ultra-p2-cash-advance-cta-1':
        this.createSalesforceOpportunity(id, url);
        break;
      default:
        break;
    }
  };

  onButtonClick = (button, index) => () => {
    const isExternal = /^http(s)?:\/\//.test(button.url);
    const isHash = !isExternal && button?.url?.indexOf('#') === 0;
    const URL = button.url;
    const internalUrl = isHash ? `${location.href}${URL}` : `/app${URL}`;
    const urlPath = isExternal ? URL : internalUrl;

    this.props.tracking.trackEvent(
      window.rzpQ
        .merchantActions()
        .initiated(`dashboard.notification_section.card.l2.cta${index + 1}`, {
          card_id: button.notificationId,
          text: button.label,
          url: button.url,
          whats_new: isWhatsNewSection(button.notificationId),
          ...this.getCommonNotificationTrackingProperties(),
        }),
    );

    if (this.lazy && !isExternal) this.props.emptySliderStack();
    if (button.id) this.handleCTA({ id: button.id, url: URL });
    else window.open(urlPath, isExternal ? '_blank' : '_self');
  };

  renderActionButtons = (buttons) => {
    return buttons.map((btn, idx) => {
      const isExternal = /^http(s)?:\/\//.test(btn.url);

      return (
        <button
          key={idx}
          onClick={this.onButtonClick(btn, idx)}
          className={classList('btn', getButtonClass(btn.type))}
        >
          {btn.label} {isExternal ? <i className="i i-external-link" /> : null}
        </button>
      );
    });
  };

  handleBackButtonClick = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('dashboard.notification_section.card.l2.back_button'),
    );
  };

  handleContentScroll = debounce((target, id, title) => {
    if (this.state.hasScrolledToEnd) return;

    if (target.scrollHeight - target.scrollTop === target.clientHeight) {
      this.props.tracking.trackEvent(
        window.rzpQ
          .merchantActions()
          .success('dashboard.notification_section.card.l2.read.display', {
            card_id: id,
            title,
            whats_new: isWhatsNewSection(id),
            ...this.getCommonNotificationTrackingProperties(),
          }),
      );

      this.setState({
        hasScrolledToEnd: true,
      });
    }
  }, 20);

  getBackButton = () => {
    const { closeUrl, popSlider } = this.props;

    if (this.lazy)
      return (
        <i
          className="i i-chevron-left"
          onClick={() => {
            this.handleBackButtonClick();
            popSlider();
          }}
        />
      );
    else if (closeUrl)
      return (
        <Link to={closeUrl} onClick={this.handleBackButtonClick}>
          <i className="i i-chevron-left" />
        </Link>
      );

    return null;
  };

  render() {
    const { id: notificationId } = this.props;
    const { buttons, title, content } =
      this.props.announcements?.find((notification) => notification.id === notificationId)
        ?.l2_content || {};

    return (
      <div className="content-wrapper content-sm announcement-details">
        <div className="panel panel-default SliderPanel announcement-details__container">
          <div className="panel-heading">
            <div className="heading-content">
              {this.getBackButton()}
              <b>Announcements</b>
            </div>
          </div>
          <div
            className="panel-body announcement-details__content"
            onScroll={({ target }) => {
              this.handleContentScroll(target, notificationId, title);
            }}
          >
            <div dangerouslySetInnerHTML={{ __html: sanitizer(content, getCustomWhiteList()) }} />
          </div>
          {buttons && (
            <div className="announcement-details__footer action-buttons">
              {this.renderActionButtons(
                buttons.map((button) => ({
                  ...button,
                  notificationId,
                })),
              )}
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      ...state.growthService.announcements,
    }),
    { popSlider: popSliderAsProp, emptySliderStack },
  ),
  rTracking(() => window.rzpQ.component('AnnouncementDetails')),
  withRouter,
)(AnnouncementDetails);
