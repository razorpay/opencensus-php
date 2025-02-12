/* eslint-disable react/no-unsafe */
/* eslint-disable no-bitwise */
import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { analyticsTrack } from 'common/utils/analytics';
import debounce from 'common/utils/debounce';
import { setItem, getItem } from 'common/utils/localStorage';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { showAcceptPaymentsModal } from 'merchant/reducers/home';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import RazorpayXNitroAnnouncement from './RazorpayXNitroAnnouncement';
import OpfinAnnouncement10L from './components/OpfinAnnouncement10L';
import OpfinAnnouncementV2 from './components/OpfinAnnouncementV2';
import { trackLoad, trackExpand, trackAnnouncement } from './ga';

const BUTTON_CLASSES = {
  button: 'btn-primary',
  'primary-inverted': 'btn-primary--invert',
};

const getButtonClass = (type) => {
  return !!BUTTON_CLASSES[type] ? BUTTON_CLASSES[type] : 'btn-link';
};

const iconMap = {
  transactions: 'i-repeat',
  settlements: 'i-done-all',
  paymentpages: 'i-payment-pages',
  invoices: 'i-notes',
  paymentlinks: 'i-link',
  marketplace: 'i-store',
  subscription: 'i-refresh',
  smartcollect: 'i-account-balance',
  reports: 'i-books',
};

const getQueryData = (param, user) => {
  switch (param) {
    case 'mid': {
      const merchant = user.merchants[user.current];

      return merchant.id;
    }
    case 'business_name': {
      return user.business_name;
    }
    case 'email': {
      return user.email;
    }
    default: {
      return null;
    }
  }
};

function _isUnreadNotification(startTS, endTS, lastReadTS) {
  return lastReadTS < startTS && moment().unix() < endTS;
}

class NotificationsDropdown extends Component {
  state = {};
  id = this.props.user.current;

  UNSAFE_componentWillMount() {
    let notifications = window.notifications || [];

    //sort notifications in most recent order using start_timestamp
    if (notifications.length > 1) {
      notifications = notifications.sort((first, second) => second.start_ts - first.start_ts);
    }

    this.setState({
      notifications,
    });

    this.setLastReadTS();
  }

  componentDidMount() {
    this.setUnreadMsgs();
    // add hubspot form
    const script = document.createElement('script');
    script.src = 'https://js.hsforms.net/forms/v2.js';
    document.body.appendChild(script);

    // add jquery for hubspot
    const scriptJQ = document.createElement('script');
    scriptJQ.src = 'https://code.jquery.com/jquery-3.5.1.min.js';
    document.body.appendChild(scriptJQ);

    this.props.tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success(
          'merchant_dashboard.display_notification',
          this.props.user.isAnnouncementIconEnabled
            ? {
                experimentVersion: 2,
              }
            : null,
        ),
    );
  }

  setUnreadMsgs() {
    const lastReadTS = this.state.lastReadTS;
    const { notifications } = this.state;
    const ID = [];
    const readID = [];
    const unreadID = [];

    let totalUnread = 0;
    for (let i = 0; i < notifications.length; i++) {
      const notifStartTS = notifications[i].start_ts;
      const notifEndTS = notifications[i].end_ts;
      const notifID = notifications[i].id;

      if (notifID) ID.push(notifID);
      if (lastReadTS < notifStartTS && moment().unix() < notifEndTS) {
        totalUnread++;

        if (notifID) unreadID.push(notifID);
      } else if (notifID) readID.push(notifID);
    }

    trackLoad(totalUnread);
    this.setState({ totalUnread });

    if (totalUnread && window.rzpQ && window.rzpQ.merchantActions) {
      const tracking = this.props.tracking;
      tracking.trackEvent(
        window.rzpQ.merchantActions().success('display.notification.bubble', {
          ID,
          readID,
          unreadID,
          ...(this.props.user.isAnnouncementIconEnabled && { experimentVersion: 2 }),
        }),
      );
    }
  }

  trackEvents = (value, url, type, id) => {
    const tracking = this.props.tracking;

    const eventName =
      type === 'button'
        ? 'dashboard.click.notification.card.cta1'
        : 'dashboard.click.notification.card.cta2';
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(eventName, {
        CTAValue: value,
        url,
        id,
      }),
    );
  };

  onShow = () => {
    analyticsTrack({
      objectName: 'announcements drop down',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'top navigation',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const tracking = this.props.tracking;
    const ID = [];
    const readID = [];
    const unreadID = [];

    this.state.notifications.forEach((notification) => {
      const notifID = notification.id;

      if (notifID) {
        ID.push(notifID);
        readID.push(notifID);
      }
    });

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('dashboard.click.notification.tab', {
        ID,
        readID,
        unreadID,
        ...(this.props.user.isAnnouncementIconEnabled && { experimentVersion: 2 }),
      }),
    );

    trackExpand(this.state.totalUnread);

    this.setState({ totalUnread: 0 });

    const newLastReadTS = moment().unix();
    setItem(`notifications-dropdown-${this.id}`, String(newLastReadTS));

    const ele = document.getElementsByClassName('Dropdown--Notifications-content')[0];

    if (ele && ele.scrollHeight > ele.offsetHeight) {
      this.setState({
        canScrollDown: true,
      });
    }

    // Mark all notifications as read
    this.state.notifications.forEach((notif) => {
      const gaAction = notif.ga && notif.ga.action ? notif.ga.action : notif.title;

      trackAnnouncement(gaAction, 'Marked as read');

      if (_isUnreadNotification(notif.start_ts, notif.end_ts, this.state.lastReadTS)) {
        trackAnnouncement(gaAction, 'Unread announcement load');
      }
    });
  };

  setLastReadTS() {
    this.setState({
      lastReadTS: getItem(`notifications-dropdown-${this.id}`) || 0,
    });
  }

  onHide = () => {
    this.setLastReadTS();
  };

  onScrollContent = (target) => {
    let canScrollDown;

    if (target.offsetHeight + target.scrollTop + 30 >= target.scrollHeight) {
      // 30 is buffer size
      canScrollDown = false;
    } else {
      canScrollDown = true;
    }

    this.setState({
      canScrollDown,
    });
  };

  showRazorpayXNitroAnnouncement = () => {
    const { closeModal, openModal } = this.props;

    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} fromWhere="announcement" />,
      size: 'xlarge',
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
  };

  handleContentScroll = debounce(this.onScrollContent.bind(this), 20);

  showOpfinAnnouncementV2 = (id) => {
    const { closeModal, openModal } = this.props;

    openModal({
      component: <OpfinAnnouncementV2 id={id} onClose={closeModal} />,
      size: 'xlarge',
      className: 'OpfinAnnouncement--Modal',
    });
  };

  showOpfinAnnouncement10L = (id) => {
    const { closeModal, openModal } = this.props;

    openModal({
      component: <OpfinAnnouncement10L id={id} onClose={closeModal} />,
      size: 'xlarge',
      className: 'OpfinAnnouncement--Modal',
    });
  };

  handleCTA = ({ id }) => {
    switch (id) {
      case 'announcement-projectNitro-cta1':
      case 'announcement-projectNitro-hyderabad-cta1':
        this.showRazorpayXNitroAnnouncement();
        break;

      case 'announcement-Nov20-Opfin-NitroV3-cta1':
        this.showOpfinAnnouncementV2(id);

        break;

      case 'announcement-Nov20-Opfin-NitroV4-cta1':
        this.showOpfinAnnouncement10L(id);

        break;

      case 'NOV20-RZP-FESTIVEOFFER-BUTTON':
        this.props.showAcceptPaymentsModal();

        break;

      default:
        break;
    }
  };

  render() {
    const { user, showMobileNav } = this.props;
    const hasUnread = !!this.state.totalUnread;
    const eventTrackingRequired = [
      'Payments-Mobile-App',
      'TwoStepVerification2020',
      'upiAutopay',
      'projectNitro',
      'paymentButton_GTM',
      'IR_update_DC',
      'NOV20-RZP-FESTIVEOFFER',
      'NOV20-VP-C1',
      'NOV20-PG-BANKUPDATE',
      'DEC20-PayPal-GTM',
      'DEC20-VP-C1',
      'Nov20-Opfin-NitroV3',
      'Nov20-Opfin-NitroV4',
      'opfin-sso-check',
      'JAN21-PG-GTM1',
      'JAN21-PG-GTM1-V2',
      'Feb20-ES1-PILOT',
      'Feb20-ES1-PILOT_V2',
      'projectNitro-hyderabad',
      'trusted-badge-mar2021',
      'trusted-badge-enabled',
      'APR23-DX-CSAT',
      'whats-new-may21-reten1-dashboard',
      'whats-new-may21-remar2a-dashboard',
      'whats-new-may21-remar1-dashboard',
      'whats-new-may21-reten2-dashboard',
      'whats-new-may21-remar2-dashboard',
      'June21-QR-GTM',
    ];
    const cardsList = this.state.notifications.map((card, idx) => (
      <div className="media media-action" key={idx}>
        <NotificationCard
          {...card}
          index={idx}
          user={user}
          lastReadTS={this.state.lastReadTS}
          trackAnnouncement={trackAnnouncement}
          trackEvents={card.id && eventTrackingRequired.includes(card.id) ? this.trackEvents : null}
          onCTAClick={this.handleCTA}
        />
      </div>
    ));

    return (
      <Dropdown closeOnClick={false} onShow={this.onShow} onHide={this.onHide}>
        <DropdownTrigger
          className={`dropdown-toggle Dropdown--Notifications-toggle${
            user.isAnnouncementIconEnabled ? ' dropdown-toggle--large-icon' : ''
          }`}
        >
          {showMobileNav ? (
            <i
              onClick={() => {
                analyticsTrack({
                  objectName: 'top nav',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: {
                    itemName: 'Announcements',
                    mobile: true,
                    location: 'top navigation',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
              className="i i-bell"
            >
              {hasUnread && <span className="red-bubble" />}
            </i>
          ) : user.isAnnouncementIconEnabled ? (
            <React.Fragment>
              <i className="i i-horn" />
              {hasUnread && <span className="new-bubble">{this.state.totalUnread}</span>}
            </React.Fragment>
          ) : (
            <React.Fragment>
              <span
                onClick={() => {
                  analyticsTrack({
                    objectName: 'top nav',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      itemName: 'Announcements',
                      location: 'top navigation',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                className={classList(hasUnread && 'highlight')}
              >
                Announcements
              </span>
              {hasUnread && <span className="bubble">{this.state.totalUnread}</span>}
            </React.Fragment>
          )}
        </DropdownTrigger>
        <DropdownContent>
          <div
            className={classList(
              'dropdown-menu Dropdown--Notifications-menu js-overflow',
              this.state.canScrollDown && 'can-scroll',
            )}
          >
            {user.current && (
              <div className="media">
                <div className="media-body">
                  <b>ANNOUNCEMENTS</b>
                </div>
              </div>
            )}

            <div
              className="Dropdown--Notifications-content"
              onScroll={({ target }) => {
                this.handleContentScroll(target);
              }}
            >
              {cardsList.length ? (
                cardsList
              ) : (
                <div className="Notifications-content-empty">
                  <img src="/img/notifications/no-notification.png" width="72px" />
                  <div className="title">No announcements right now</div>
                </div>
              )}
            </div>
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}

function getAgoLabel(ts) {
  const now = moment().unix();
  const seconds = (now - ts) >> 0;
  const hours = seconds / 3600;
  const days = hours / 24;
  const years = days / 365;

  let tsLabel;

  if (hours < 24) {
    tsLabel = 'Today';
  } else if (days < 30) {
    tsLabel = `${Math.floor(days)} day${days > 2 ? 's' : ''} ago`;
  } else if (days > 30 && days < 365) {
    tsLabel = `${Math.floor(days / 30)} month${days > 60 ? 's' : ''} ago`;
  } else if (years > 1) {
    tsLabel = `${Math.floor(years)}year ago`;
  }

  return tsLabel;
}

const NotificationCard = ({
  user,
  icon,
  start_ts,
  end_ts,
  title,
  description,
  buttons,
  lastReadTS,
  trackAnnouncement,
  trackEvents,
  ga,
  id,
  index,
  onCTAClick,
}) => {
  const isUnread = _isUnreadNotification(start_ts, end_ts, lastReadTS);
  return (
    <div
      className={classList(
        'NotificationCard',
        isUnread ? 'active' : 'inactive', // Notification is not read and also not expiry
      )}
    >
      <span className="NotificationCard-icon">
        {iconMap[icon] ? (
          <i className={`ico i ${iconMap[icon]}`}>{isUnread && <span className="red-bubble" />}</i>
        ) : (
          <span className="ico">
            <img src={icon} width="32px" />
            {isUnread && <span className="red-bubble" />}
          </span>
        )}
      </span>
      <div className="NotificationCard-body">
        <div className="heading">
          <div className="title">
            <b>{title}</b>
          </div>
          <div className="timestamp">{getAgoLabel(start_ts)}</div>
        </div>
        <div className="description">{description}</div>
        <div className="action-buttons">
          {buttons.map((btn, idx) => {
            const isExternal = /^http(s)?:\/\//.test(btn.url);
            const isHash = !isExternal && btn.url.indexOf('#') === 0;

            let URL = btn.url;

            if (btn.url_query_params) {
              URL = `${URL}?`;

              btn.url_query_params.forEach((param, i) => {
                const data = getQueryData(param, user);

                if (i === 0) {
                  URL = `${URL}${param}=${data}`;

                  return;
                }

                URL = `${URL}&${param}=${data}`;
              });
            }

            const internalUrl = isHash ? `${location.href}${URL}` : `/app${URL}`;
            const urlPath = isExternal ? URL : internalUrl;

            return (
              <a
                key={idx}
                className={classList('btn', getButtonClass(btn.type))}
                onClick={(e) => {
                  analyticsTrack({
                    objectName: 'announcements',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      new: isUnread,
                      date: start_ts,
                      sequence: index,
                      actionName: btn.label,
                      title,
                      location: 'top navigation',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  trackAnnouncement(
                    ga ? ga.action : title,
                    `CTA Click - ${btn.label} - ${isUnread ? 'unread' : 'read'}`,
                  );
                  trackEvents && trackEvents(btn.label, urlPath, btn.type, id);
                  // distinguish between links and buttons that open modals
                  if (btn.id) {
                    e.preventDefault();
                    onCTAClick({ id: btn.id });
                  }
                }}
                href={urlPath}
                target={isExternal ? '_blank' : ''}
                rel="noreferrer"
              >
                <b>
                  {btn.label} {isExternal && <i className="i i-external-link" />}
                </b>
              </a>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default compose(
  connect(
    (state) => {
      return {
        ...state.session,
        ...state.config.config,
      };
    },
    {
      openModal,
      closeModal,
      showAcceptPaymentsModal,
    },
  ),
  rTracking({
    page: 'NotificationsDropdown',
  }),
  withRouter,
)(NotificationsDropdown);
