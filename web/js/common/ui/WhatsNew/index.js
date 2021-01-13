import { Component, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';
import LocalStorageService from 'common/utils/localStorage';
import { classList } from 'common/utils/rzp-utils';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { trackLoad, trackExpand, trackAnnouncement, track } from '../NotificationsDropdown/ga';
import HubspotCAForm from '../NotificationsDropdown/HubspotCAForm';
import { showAcceptPaymentsModal } from 'merchant/reducers/home';
import OpfinAnnouncement from '../NotificationsDropdown/components/OpfinAnnouncement';
import OpfinAnnouncementV2 from '../NotificationsDropdown/components/OpfinAnnouncementV2';
import OpfinAnnouncement10L from '../NotificationsDropdown/components/OpfinAnnouncement10L';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { openSlider, closeSlider } from 'merchant_common/reducers/slider';
import Slider from 'common/ui/Slider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Spinner from 'common/ui/Spinner';
import './WhatsNew.styl';

function _isUnreadNotification(startTS, endTS, lastReadTS) {
  return lastReadTS < startTS && moment().unix() < endTS;
}

@withRouter
@connect(
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
    openSlider,
    closeSlider,
  },
)
@RTracking(() => window.rzpQ.component('WhatsNew'))
export default class WhatsNew extends Component {
  state = {
    isOpenSlider1: false,
    showTooltip: false,
  };
  id = this.props.user.current;
  whatsNew = false;

  componentWillMount() {
    let notifications = window.notifications || [];

    //sort notifications in most recent order using start_timestamp
    if (notifications.length > 1) {
      notifications = notifications.sort((first, second) => second.start_ts - first.start_ts);
    }

    this.setState({
      notifications: notifications,
    });

    this.setLastReadTS();
  }

  componentDidMount() {
    this.setUnreadMsgs();

    // add jquery for hubspot
    const scriptJQ = document.createElement('script');
    scriptJQ.src = 'https://code.jquery.com/jquery-3.5.1.min.js';
    document.body.appendChild(scriptJQ);

    // add script for youtube iframe api
    var tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    this.props.tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success(
          'merchant_dashboard.display_notification',
          this.whatsNew
            ? {
                whats_new: true,
              }
            : null,
        ),
    );

    document.addEventListener('click', this.handleDocumentClick, true);
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.handleDocumentClick, true);
  }

  setUnreadMsgs() {
    const lastReadTS = this.state.lastReadTS;
    const { notifications } = this.state;
    const ID = [],
      readID = [],
      unreadID = [];

    let totalUnread = 0;
    for (let i = 0; i < notifications.length; i++) {
      const notifStartTS = notifications[i].start_ts;
      const notifEndTS = notifications[i].end_ts;
      const notifID = notifications[i].id;

      this.whatsNew =
        (notifID && notifID.length >= 9 && notifID.substring(0, 9) === 'whats-new') ||
        this.whatsNew;

      if (notifID) ID.push(notifID);
      if (lastReadTS < notifStartTS && moment().unix() < notifEndTS) {
        totalUnread++;

        if (notifID) unreadID.push(notifID);
      } else if (notifID) readID.push(notifID);
    }

    trackLoad(totalUnread);
    this.setState({ totalUnread, ID, unreadID, readID });

    if (totalUnread) {
      const tracking = this.props.tracking;
      tracking.trackEvent(
        window.rzpQ.merchantActions().success('display.notification.bubble', {
          ID,
          readID,
          unreadID,
          ...(this.whatsNew && { whats_new: true }),
        }),
      );
    }
  }

  toggleHubSpotCAForm = () => {
    this.setState({ showHubSpotCAForm: !this.state.showHubSpotCAForm });
  };

  showOpfinAnnouncement = (id) => {
    const { closeModal, openModal } = this.props;

    openModal({
      component: <OpfinAnnouncement id={id} onClose={closeModal} />,
      size: 'xlarge',
      className: 'OpfinAnnouncement--Modal',
    });
  };

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
        this.toggleHubSpotCAForm();
        break;
      case 'announcement-Nov20-Opfin-NitroV1-cta1':
      case 'announcement-Nov20-Opfin-NitroV2-cta1':
        this.showOpfinAnnouncement(id);
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

  setLastReadTS() {
    this.setState({
      lastReadTS: LocalStorageService.getItem('announcements-slider-' + this.id) || 0,
    });
  }

  onShow = () => {
    analyticsService.track({
      objectName: 'announcements drop down',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'top navigation',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('dashboard.notification_section.read', {
        unreadID: this.state.unreadID,
        count_unread_IDs: this.state.unreadID.length,
        ...(this.whatsNew && { whats_new: true }),
      }),
    );

    const ID = [],
      readID = [],
      unreadID = [];

    this.state.notifications.forEach((notification) => {
      const notifID = notification.id;

      if (notifID) {
        ID.push(notifID);
        readID.push(notifID);
      }
    });

    trackExpand(this.state.totalUnread);

    this.setState({ totalUnread: 0, ID, unreadID, readID });

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('dashboard.click.notification.tab', {
        ID,
        readID,
        unreadID,
        ...(this.whatsNew && { whats_new: true }),
      }),
    );

    const newLastReadTS = moment().unix();
    LocalStorageService.setItem('announcements-slider-' + this.id, String(newLastReadTS));

    // Mark all notifications as read
    this.state.notifications.forEach((notif) => {
      const gaAction = notif.ga && notif.ga.action ? notif.ga.action : notif.title;

      trackAnnouncement(gaAction, 'Marked as read');

      if (_isUnreadNotification(notif.start_ts, notif.end_ts, this.state.lastReadTS)) {
        trackAnnouncement(gaAction, 'Unread announcement load');
      }
    });
  };

  trackEvents = (value, url, type, id) => {
    const tracking = this.props.tracking;
    const whatsNew = id && id.length >= 9 && id.substring(0, 9) === 'whats-new';

    const eventName =
      type === 'button'
        ? 'dashboard.click.notification.card.cta1'
        : 'dashboard.click.notification.card.cta2';
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(eventName, {
        CTAValue: value,
        url: url,
        id: id,
        ...(whatsNew && { whats_new: true }),
      }),
    );
  };

  handleDocumentClick = (event) => {
    const target = event.target;
    const sliderContent = document.querySelector('.content-wrapper.whats-new');
    const sliderToggle = document.querySelector('.whats-new-slide-toggle');
    const whatsNewTooltip = document.querySelector('.whats-new__tooltip');
    const announcementDetails = document.querySelector(
      '.panel.panel-default.SliderPanel.announcement-details__container',
    );
    if (
      (sliderContent && sliderContent.contains(target)) ||
      (sliderToggle && sliderToggle.contains(target)) ||
      (whatsNewTooltip && whatsNewTooltip.contains(target)) ||
      (announcementDetails && announcementDetails.contains(target))
    ) {
      return;
    }
    this.closeTooltip();
    this.setState({ isOpenSlider1: false });
    this.setLastReadTS();
  };

  closeTooltip = () => {
    this.setState({ showTooltip: false });
  };

  showTooltip = () => {
    this.setState({ showTooltip: true });
  };

  setTooltipVisibility = () => {
    const tooltipCookie = Number(LocalStorageService.getItem('whats-new-tooltip-count-' + this.id));
    const tooltipViewCount = tooltipCookie === NaN ? 0 : tooltipCookie;
    if (tooltipViewCount < 3) {
      LocalStorageService.setItem('whats-new-tooltip-count-' + this.id, tooltipViewCount + 1);
      this.props.tracking.trackEvent(
        window.rzpQ.merchantActions().success('dashboard.notification_section.tool_tip.display', {
          tooltip_display_count: tooltipViewCount + 1,
          ...(this.whatsNew && { whats_new: true }),
        }),
      );
      this.showTooltip();
    } else {
      this.closeTooltip();
    }
  };

  hideSlider = () => {
    this.props.closeSlider();
    this.closeTooltip();
    this.setState({ isOpenSlider1: false });
    this.setLastReadTS();
  };

  showSlider = () => {
    this.props.openSlider();
    this.setTooltipVisibility();
    this.setState({ isOpenSlider1: true });
    this.onShow();
  };

  handleSliderToggleClick = () => {
    const { isOpenSlider1 } = this.state;
    if (isOpenSlider1) this.hideSlider();
    else this.showSlider();
  };

  render() {
    const { lastReadTS, isOpenSlider1, totalUnread, showTooltip } = this.state;
    const { user, history } = this.props;
    const { showHubSpotCAForm } = this.state;

    const eventTrackingRequired = [
      'Payments-Mobile-App',
      'TwoStepVerification2020',
      'upiAutopay',
      'projectNitro',
      'paymentButton_GTM',
      'IR_update_DC',
      'NOV20-RZP-FESTIVEOFFER',
      'Nov20-Opfin-NitroV1',
      'NOV20-VP-C1',
      'NOV20-PG-BANKUPDATE',
      'Nov20-Opfin-NitroV2',
      'DEC20-PayPal-GTM',
    ];
    const hasUnread = !!this.state.totalUnread;

    let cardsList = window.notifications.map((card, idx) => (
      <div className="media media-action" key={idx}>
        <NotificationCard
          {...card}
          index={idx}
          user={user}
          lastReadTS={lastReadTS}
          trackAnnouncement={trackAnnouncement}
          trackEvents={card.id && eventTrackingRequired.includes(card.id) ? this.trackEvents : null}
          onCTAClick={this.handleCTA}
          history={history}
          tracking={this.props.tracking}
        />
      </div>
    ));

    return (
      <main className={classList('whats-new', isOpenSlider1 && 'whats-new--active')}>
        <div className="whats-new-slide-toggle">
          <i className="i i-horn" onClick={this.handleSliderToggleClick}></i>
          {hasUnread && <span class="new-bubble">{totalUnread}</span>}
        </div>
        <div
          className={classList(
            'whats-new__tooltip',
            isOpenSlider1 && showTooltip && 'whats-new__tooltip--show',
          )}
        >
          <div className="whats-new__content">
            <div className="whats-new__heading">
              <span>🔥 What’s New?</span>
              <i className="i i-close" onClick={this.closeTooltip}></i>
            </div>
            <div className="whats-new__body">
              New Features, Bug Fixes and Product Updates that you might have missed!
            </div>
          </div>
        </div>
        <Slider closeButtonClass="announcement-title">
          <ErrorBoundary resetOnProps>
            <div class="content-wrapper content-sm txn-details whats-new">
              <div class="panel panel-default SliderPanel">
                <div class="panel-heading">
                  <div class="heading-content">
                    <div class="title">
                      <b>Announcements</b>
                    </div>
                  </div>
                </div>
                <div class="SliderPanel__Body">
                  <div class="panel-body">
                    <div class="whats-new-content">
                      {cardsList.length ? (
                        cardsList
                      ) : (
                        <div class="Notifications-content-empty">
                          <img src="/img/notifications/no-notification.png" width="72px" />
                          <div class="title">No announcements right now</div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <HubspotCAForm
              shouldShowModal={showHubSpotCAForm}
              hideModal={this.toggleHubSpotCAForm}
              fromWhere="announcement"
            />
          </ErrorBoundary>
        </Slider>
      </main>
    );
  }
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
  video_url,
  secondary_icon,
  index,
  onCTAClick,
  history,
  tracking,
}) => {
  const isUnread = _isUnreadNotification(start_ts, end_ts, lastReadTS);
  const [isLoading, setIsLoading] = useState(true);

  const onYouTubePlayer = () => {
    const onPlayerStateChange = (event) => {
      const whatsNew = id && id.length >= 9 && id.substring(0, 9) === 'whats-new';

      if (event.data == YT.PlayerState.PLAYING) {
        tracking.trackEvent(
          window.rzpQ.merchantActions().success('dashboard.notification_section.card.display', {
            card_id: id,
            video_url,
            ...(whatsNew && { whats_new: true }),
          }),
        );
      }
    };

    let player = new window.YT.Player(`player-${id}`, {
      videoId: video_url.split('/').slice(-1)[0],
      events: {
        onStateChange: onPlayerStateChange,
      },
    });
  };

  useEffect(() => {
    if (video_url && video_url.length) {
      if (typeof YT == 'undefined' || typeof YT.Player == 'undefined') {
        window.onYouTubePlayerAPIReady = () => {
          onYouTubePlayer();
        };
      } else {
        onYouTubePlayer();
      }
    }
  }, []);

  const handleCTAClick = (e, btn, urlPath) => {
    analyticsService.track({
      objectName: 'announcements',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        new: isUnread,
        date: start_ts,
        sequence: index,
        actionName: btn.label,
        title: title,
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
    if (btn.id === 'announcement-details-l2') {
      e.preventDefault();
      history.push(btn.url);
    } else if (btn.id) {
      e.preventDefault();
      onCTAClick({ id: btn.id });
    }
  };

  return (
    <div
      class={classList(
        'NotificationCard',
        isUnread ? 'active' : 'inactive', // Notification is not read and also not expiry
      )}
    >
      <span class="NotificationCard-icon">
        {iconMap[icon] ? (
          <i class={`ico i ${iconMap[icon]}`}>{isUnread && <span class="red-bubble" />}</i>
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
            <p>{title}</p>
          </div>
          {secondary_icon && secondary_icon.length ? (
            <span class="NotificationCard-icon--secondary">
              {iconMap[icon] ? (
                <i class={`ico i ${iconMap[secondary_icon]}`} />
              ) : (
                <span class="ico">
                  <img src={secondary_icon} />
                </span>
              )}
            </span>
          ) : null}
        </div>
        {video_url && video_url.length ? (
          <div id={`player-${id}`} className="whats-new__video-small">
            <iframe
              src={video_url}
              frameBorder="0"
              allow="autoplay; encrypted-media"
              allowFullScreen
              title={title}
            />
          </div>
        ) : null}
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

            let internalUrl = isHash ? `${location.href}${URL}` : `/app${URL}`;
            const urlPath = isExternal ? URL : internalUrl;

            return (
              <a
                key={idx}
                class={classList('btn', getButtonClass(btn.type))}
                onClick={(e) => handleCTAClick(e, btn, urlPath)}
                href={urlPath}
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
