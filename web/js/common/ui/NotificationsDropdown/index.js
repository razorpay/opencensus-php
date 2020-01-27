import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import debounce from 'common/utils/debounce';
import LocalStorageService from 'common/utils/localStorage';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList } from 'common/utils/rzp-utils';

import { trackLoad, trackExpand, trackAnnouncement } from './ga';

function _isUnreadNotification(startTS, endTS, lastReadTS) {
  return lastReadTS < startTS && moment().unix() < endTS;
}

@withRouter
@connect(state => {
  return {
    ...state.session,
    ...state.config.config,
  };
})
export default class NotificationsDropdown extends Component {
  state = {};
  id = this.props.user.current;

  componentWillMount() {
    let notifications = window.notifications || [];

    //sort notifications in most recent order using start_timestamp
    if (notifications.length > 1) {
      notifications = notifications.sort(
        (first, second) => second.start_ts - first.start_ts
      );
    }

    this.setState({
      notifications: notifications,
    });

    this.setLastReadTS();
  }

  componentDidMount() {
    this.setUnreadMsgs();
  }

  setUnreadMsgs() {
    const lastReadTS = this.state.lastReadTS;
    const { notifications } = this.state;

    let totalUnread = 0;
    for (let i = 0; i < notifications.length; i++) {
      const notifStartTS = notifications[i].start_ts;
      const notifEndTS = notifications[i].end_ts;

      if (lastReadTS < notifStartTS && moment().unix() < notifEndTS) {
        totalUnread++;
      }
    }

    trackLoad(totalUnread);
    this.setState({ totalUnread });
  }

  onShow = () => {
    trackExpand(this.state.totalUnread);

    this.setState({ totalUnread: 0 });

    const newLastReadTS = moment().unix();
    LocalStorageService.setItem(
      'notifications-dropdown-' + this.id,
      String(newLastReadTS)
    );

    const ele = document.getElementsByClassName(
      'Dropdown--Notifications-content'
    )[0];

    if (ele && ele.scrollHeight > ele.offsetHeight) {
      this.setState({
        canScrollDown: true,
      });
    }

    // Mark all notifications as read
    this.state.notifications.forEach(notif => {
      const gaAction =
        notif.ga && notif.ga.action ? notif.ga.action : notif.title;

      trackAnnouncement(gaAction, 'Marked as read');

      if (
        _isUnreadNotification(
          notif.start_ts,
          notif.end_ts,
          this.state.lastReadTS
        )
      ) {
        trackAnnouncement(gaAction, 'Unread announcement load');
      }
    });
  };

  setLastReadTS() {
    this.setState({
      lastReadTS:
        LocalStorageService.getItem('notifications-dropdown-' + this.id) || 0,
    });
  }

  onHide = () => {
    this.setLastReadTS();
  };

  onScrollContent = target => {
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

  handleContentScroll = debounce(::this.onScrollContent, 20);

  render() {
    let { user, showMobileNav, analytics = () => {} } = this.props;
    const hasUnread = !!this.state.totalUnread;

    let cardsList = this.state.notifications.map((n, idx) => (
      <div className="media media-action" key={idx}>
        <NotificationCard
          {...n}
          user={user}
          lastReadTS={this.state.lastReadTS}
          trackAnnouncement={trackAnnouncement}
        />
      </div>
    ));

    return (
      <Dropdown closeOnClick={false} onShow={this.onShow} onHide={this.onHide}>
        <DropdownTrigger class="dropdown-toggle Dropdown--Notifications-toggle">
          {showMobileNav ? (
            <React.Fragment>
              <i class="i i-bell">{hasUnread && <span class="red-bubble" />}</i>
            </React.Fragment>
          ) : (
            <React.Fragment>
              <span class={classList(hasUnread && 'highlight')}>
                Announcements
              </span>
              {hasUnread && (
                <span class="bubble">{this.state.totalUnread}</span>
              )}
            </React.Fragment>
          )}
        </DropdownTrigger>
        <DropdownContent>
          <div
            class={classList(
              'dropdown-menu Dropdown--Notifications-menu js-overflow',
              this.state.canScrollDown && 'can-scroll'
            )}
          >
            {user.current && (
              <div class="media">
                <div class="media-body">
                  <b>ANNOUNCEMENTS</b>
                </div>
              </div>
            )}

            <div
              class="Dropdown--Notifications-content"
              onScroll={({ target }) => {
                this.handleContentScroll(target);
              }}
            >
              {cardsList.length ? (
                cardsList
              ) : (
                <div class="Notifications-content-empty">
                  <img
                    src="img/notifications/no-notification.png"
                    width="72px"
                  />
                  <div class="title">No announcements right now</div>
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
    tsLabel = Math.floor(days) + ' day' + (days > 2 ? 's' : '') + ' ago';
  } else if (days > 30 && days < 365) {
    tsLabel =
      Math.floor(days / 30) + ' month' + (days > 60 ? 's' : '') + ' ago';
  } else if (years > 1) {
    tsLabel = Math.floor(years) + 'year ago';
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
  ga,
}) => {
  const isUnread = _isUnreadNotification(start_ts, end_ts, lastReadTS);
  return (
    <div
      class={classList(
        'NotificationCard',
        isUnread ? 'active' : 'inactive' // Notification is not read and also not expiry
      )}
    >
      <span class="NotificationCard-icon">
        {iconMap[icon] ? (
          <i class={`ico i ${iconMap[icon]}`}>
            {isUnread && <span class="red-bubble" />}
          </i>
        ) : (
          <span class="ico">
            <img src={icon} width="32px" />
            {isUnread && <span class="red-bubble" />}
          </span>
        )}
      </span>
      <div class="NotificationCard-body">
        <div class="heading">
          <div class="title">
            <b>{title}</b>
          </div>
          <div class="timestamp">{getAgoLabel(start_ts)}</div>
        </div>
        <div class="description">{description}</div>
        <div class="action-buttons">
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

            let internalUrl = isHash ? `${location.href}${URL}` : `#/app${URL}`;

            return (
              <a
                key={idx}
                class={classList('btn', getButtonClass(btn.type))}
                onClick={e => {
                  trackAnnouncement(
                    ga ? ga.action : title,
                    `CTA Click - ${btn.label} - ${isUnread ? 'unread' : 'read'}`
                  );
                }}
                href={isExternal ? URL : internalUrl}
                target={isExternal ? '_blank' : ''}
              >
                <b>
                  {btn.label} {isExternal && <i class="i i-external-link" />}
                </b>
              </a>
            );
          })}
        </div>
      </div>
    </div>
  );
};

const BUTTON_CLASSES = {
  button: 'btn-primary',
  'primary-inverted': 'btn-primary--invert',
};

const getButtonClass = type => {
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
