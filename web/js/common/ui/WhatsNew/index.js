import React, { Component, useEffect, Suspense, useRef } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ExclusiveOffer from 'common/ui/ExclusiveOffer';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import GrowthServiceModal from 'common/ui/GrowthServiceModal';
import GrowthServiceCenterCTAModal from 'common/ui/GrowthServiceModal/CenterCTAModal';
import GrowthServiceThankYouModal from 'common/ui/GrowthServiceModal/ThankYouModal';
import Loader from 'common/ui/Loader';
import RazorpayXNitroAnnouncement from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import OpfinAnnouncement10L from 'common/ui/NotificationsDropdown/components/OpfinAnnouncement10L';
import OpfinAnnouncementV2 from 'common/ui/NotificationsDropdown/components/OpfinAnnouncementV2';
import { trackExpand, trackAnnouncement } from 'common/ui/NotificationsDropdown/ga';
import { analyticsTrack } from 'common/utils/analytics';
import { sendDataToSalesForce } from 'common/utils/common-api';
import debounce from 'common/utils/debounce';
import { getItem, setItem } from 'common/utils/localStorage';
import {
  classList,
  getCommonAnalyticsProperties,
  isMobileAndTablet,
  isElementXPercentInViewport,
  linkFromSource,
  getYoutubeVideoID,
} from 'common/utils/rzp-utils';
import getSurveyForm from 'merchant/components/Announcements/CSATSurveyBanner/getSurveyForm';
import MobileAppQRCode from 'merchant/components/MobileAppQRCode';
import growthServiceCTAHandler from 'merchant/models/GrowthService/growthServiceCTAHandler';
import {
  setActivePageName as fnSetActivePageName,
  setBaseLocation as fnSetBaseLocation,
} from 'merchant/reducers/app';
import { fetchAnnouncements } from 'merchant/reducers/growthService';
import { showAcceptPaymentsModal } from 'merchant/reducers/home';
import lazy from 'merchant/routes/LazyLoader';
import {
  closeModal as closeModalx,
  openModal as openModalx,
} from 'merchant_common/reducers/modals';
import {
  pushSlider as pushSliderx,
  emptySliderStack as emptySliderStackx,
} from 'merchant_common/reducers/multiSlider';

import './WhatsNew.styl';
import {
  getNotificationsReadData,
  getExperimentVersion,
  getButtonClass,
  iconMap,
  getQueryData,
  getNotificationTrackingProperties,
} from './common';

const WhatsNewDetailsPage = lazy(() =>
  import(/* webpackChunkName: "WhatsNewDetailsPage" */ 'merchant/views/WhatsNew/Details'),
);

function _isUnreadNotification(startTS, endTS, lastReadTS) {
  return lastReadTS < startTS && moment().unix() < endTS;
}

class WhatsNew extends Component {
  state = {
    showTooltip: false,
  };
  notificationsRefsList = [];
  id = this.props.user.current;

  UNSAFE_componentWillMount = () => {
    this.props.fetchAnnouncements({ fromWhere: 'home' });
    this.setLastReadTS();
  };

  componentDidUpdate = (prevProps) => {
    if (prevProps.loading != this.props.loading && this.props.loading === false) {
      this.setUnreadMsgs();
    }
  };

  componentDidMount() {
    this.setTooltipVisibility();

    // add jquery for hubspot
    const scriptJQ = document.createElement('script');
    scriptJQ.src = 'https://code.jquery.com/jquery-3.5.1.min.js';
    document.body.appendChild(scriptJQ);

    // add script for youtube iframe api
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
  }

  setUnreadMsgs() {
    const { totalUnread, unreadID } = getNotificationsReadData(this.id);
    this.setState({ totalUnread, unreadID }, () => this.onShow());
  }

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

  showRazorpayXNitroAnnouncement = () => {
    const { closeModal, openModal } = this.props;

    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} fromWhere="whatsnew" />,
      size: 'xlarge',
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
  };

  showGSExclusiveOfferModal = () => {
    const { openModal } = this.props;

    openModal({
      component: <ExclusiveOffer />,
      size: 'xlarge',
      className: 'GSExclusiveOffer--Modal',
    });
  };

  showGSModal = (id, tracking_id) => {
    const { openModal } = this.props;
    openModal({
      component: <GrowthServiceModal template_id={id} tracking_id={tracking_id} />,
      className: 'gs-modal',
    });
  };

  showGSModalMobile = (id, tracking_id) => {
    const { openModal } = this.props;
    openModal({
      component: <GrowthServiceModal template_id={id} tracking_id={tracking_id} />,
    });
  };

  showGSCenterCTAModal = (id) => {
    const { openModal } = this.props;
    openModal({
      component: <GrowthServiceCenterCTAModal template_id={id} />,
      className: 'gs-modal',
    });
  };

  showGSThankYouModal = (id) => {
    const { openModal } = this.props;
    return openModal({
      component: <GrowthServiceThankYouModal template_id={id} />,
      size: 'medium',
    });
  };

  onMobileAppCampaignCTAClick = () => {
    // handle the popup open here. refer showRazorpayXNitroAnnouncement function
    const { user } = this.props;
    const isMWeb = isMobileAndTablet();
    const isActivated = user.activation_status === 'activated';
    const mWebUrl = isActivated
      ? 'https://razorpay.app.link/WXejnLoeVgb'
      : 'https://razorpay.app.link/eQpmmyD4ygb';

    if (isMWeb) window.open(mWebUrl, '_blank').focus();
    else
      this.props.openModal({
        component: <MobileAppQRCode />,
        size: 'small',
      });
  };

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
        return;
      }
    }

    sendDataToSalesForce(event, this.props.user);

    this.props.history.push(url);
  };

  openZapierIntentForm = () => {
    const form = getSurveyForm(this.props.user, null, 'zRcUmSBp');
    form.open();
  };

  handleConnectedBankingFlow = () => {
    this.props.history.push('/connected-banking/icici-linked-ca');
    this.props.setBaseLocation('/connected-banking/icici-linked-ca');
    this.props.setActivePageName('Connected Banking');
  };

  handleCTA = ({ id, url, type, variant, handler, history, tracking_id }) => {
    const { tracking } = this.props;
    const isMWeb = isMobileAndTablet();
    if (type?.length && variant?.length) {
      if (isMWeb) {
        switch (type) {
          case 'MODAL':
            switch (variant) {
              case 'default':
                this.showGSModalMobile(id, tracking_id);
                break;
              case 'thank-you':
                this.showGSThankYouModal(id);
                break;
              default:
                break;
            }
            break;
          default:
        }
      } else {
        switch (type) {
          case 'MODAL':
            switch (variant) {
              case 'default':
                this.showGSModal(id, tracking_id);
                break;
              case 'center-cta':
                this.showGSCenterCTAModal(id);
                break;
              case 'thank-you':
                this.showGSThankYouModal(id);
                break;
              default:
                break;
            }
            break;
          default:
        }
      }
      return;
    }
    if (handler) {
      growthServiceCTAHandler(handler, history, tracking_id, tracking);
    }
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
      case 'cash-advance-cta-1':
      case 'whats-new-JUN21-RXCC-GROWTH-cta1':
      case 'ultra-campaign-announcement-cta-1':
      case 'ultra-p2-cash-advance-cta-1':
        this.createSalesforceOpportunity(id, url);
        break;
      case 'announcement-May21-PLMApp-GTM':
        this.onMobileAppCampaignCTAClick();
        break;
      case 'Aug25-AppStore-Intent-Zapier-cta':
        this.openZapierIntentForm();
        break;
      case 'GS-Exclusive-Offer-modal':
        this.showGSExclusiveOfferModal();
        break;
      case 'JAN22-ICICI-CONNECTEDBANKING-ANN':
        this.handleConnectedBankingFlow();
        break;
      default:
        break;
    }
  };

  setLastReadTS() {
    this.setState({
      lastReadTS: getItem(`announcements-slider-${this.id}`) || 0,
    });
  }

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

    const { tracking, user, announcements } = this.props;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('dashboard.notification_section.read', {
        unreadID: this.state.unreadID,
        count_unread_IDs: this.state.unreadID?.length,
        lazy: true,
      }),
    );

    const { ID, readID, unreadID } = getNotificationsReadData(this.id);

    trackExpand(this.state.totalUnread);

    this.setState({ totalUnread: 0, unreadID });

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('dashboard.click.notification.tab', {
        trackingID: ID,
        readID,
        unreadID,
        experimentVersion: getExperimentVersion(user),
        lazy: true,
      }),
    );

    const newLastReadTS = moment().unix();
    setItem(`announcements-slider-${this.id}`, String(newLastReadTS));

    // Mark all notifications as read
    announcements?.forEach((notif) => {
      const gaAction = notif.ga && notif.ga.action ? notif.ga.action : notif.title;

      trackAnnouncement(gaAction, 'Marked as read');

      if (_isUnreadNotification(notif.start_ts, notif.end_ts, this.state.lastReadTS)) {
        trackAnnouncement(gaAction, 'Unread announcement load');
      }
    });
  };

  trackEvents = (value, url, type, id, notification, image_url, video_url) => {
    const { tracking } = this.props;

    const eventName =
      type === 'button'
        ? 'dashboard.click.notification.card.cta1'
        : 'dashboard.click.notification.card.cta2';
    let mediaType = '';
    if (image_url && video_url) {
      mediaType = 'video&image';
    } else if (image_url) {
      mediaType = 'only-image';
    } else if (video_url) {
      mediaType = 'only-video';
    } else {
      mediaType = 'no-media';
    }
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(eventName, {
        CTAValue: value,
        url,
        ...getNotificationTrackingProperties(notification, eventName),
        trackingID: id,
        lazy: true,
        mediaType,
      }),
    );
  };

  closeTooltip = () => {
    this.setState({ showTooltip: false });
  };

  showTooltip = () => {
    this.setState({ showTooltip: true });
  };

  setTooltipVisibility = () => {
    const tooltipCookie = Number(getItem(`whats-new-tooltip-count-${this.id}`));
    const tooltipViewCount = isNaN(tooltipCookie) ? 0 : tooltipCookie;
    if (tooltipViewCount < 3) {
      setItem(`whats-new-tooltip-count-${this.id}`, tooltipViewCount + 1);
      this.props.tracking.trackEvent(
        window.rzpQ.merchantActions().success('dashboard.notification_section.tool_tip.display', {
          tooltip_display_count: tooltipViewCount + 1,
          lazy: true,
        }),
      );

      setTimeout(() => this.showTooltip(), 500); // for the tooltip animation
    }
  };

  trackOnCardView = () => {
    let index = this.notificationsRefsList.length - 1;

    while (index >= 0) {
      const cardElement = this.notificationsRefsList[index]?.ref?.current;
      const position = this.notificationsRefsList[index]?.position;
      if (cardElement && isElementXPercentInViewport(cardElement, 75, 116)) {
        const eventName = 'dashboard.click.notification.card.viewed';

        const payload = {
          trackingID: cardElement.getAttribute('id'),
          position,
          ...getNotificationTrackingProperties(
            this.notificationsRefsList[index]?.notification,
            eventName,
          ),
        };

        this.props.tracking.trackEvent(window.rzpQ.merchantActions().success(eventName, payload));
        this.notificationsRefsList.splice(index, 1);
      }
      if (!cardElement) this.notificationsRefsList.splice(index, 1);
      index--;
    }
  };

  renderNotifications = () => {
    const { lastReadTS } = this.state;
    const { user, history, announcements } = this.props;

    const cardsList = announcements?.map((card, idx) => (
      <div className="media media-action" key={idx}>
        <NotificationCard
          {...card}
          index={idx}
          user={user}
          lastReadTS={lastReadTS}
          trackEvents={this.trackEvents}
          onCTAClick={this.handleCTA}
          history={history}
          tracking={this.props.tracking}
          pushSlider={this.props.pushSlider}
          emptySliderStack={this.props.emptySliderStack}
          addOwnRef={(ref, position, notification) => {
            this.notificationsRefsList.push({ ref, position, notification });
          }}
          notificationRef={this.notificationsRefsList}
        />
      </div>
    ));

    return cardsList;
  };

  render() {
    const { announcements, loading } = this.props;
    const { showTooltip } = this.state;
    let contentToShow = null;

    if (loading) contentToShow = <Loader />;
    else if (announcements?.length) contentToShow = this.renderNotifications();
    else {
      contentToShow = (
        <div className="Notifications-content-empty">
          <img src="/img/notifications/no-notification.png" width="72px" />
          <div className="title">No announcements right now</div>
        </div>
      );
    }

    return (
      <div className="whats-new">
        <div className={classList('whats-new__tooltip', showTooltip && 'whats-new__tooltip--show')}>
          <div className="whats-new__content">
            <div className="whats-new__heading">
              <span>🔥 What’s New?</span>
              <i className="i i-close" onClick={this.closeTooltip} />
            </div>
            <div className="whats-new__body">
              New Features, Bug Fixes and Product Updates that you might have missed!
            </div>
          </div>
        </div>
        <GrowthAssetEB shouldShowDefaultFb>
          <div className="content-wrapper content-sm txn-details whats-new">
            <div className="panel panel-default SliderPanel">
              <div className="panel-heading">
                <div className="heading-content">
                  <div className="title">
                    <b>Announcements</b>
                  </div>
                </div>
              </div>
              <div className="SliderPanel__Body" onScroll={debounce(this.trackOnCardView, 100)}>
                <div className="panel-body">
                  <div className="whats-new-content">{contentToShow}</div>
                </div>
              </div>
            </div>
          </div>
        </GrowthAssetEB>
      </div>
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
  trackEvents,
  ga,
  id,
  image_url,
  video_url,
  secondary_icon,
  index,
  onCTAClick,
  history,
  tracking,
  pushSlider,
  emptySliderStack,
  addOwnRef,
  notificationRef,
  ...notification
}) => {
  const isUnread = _isUnreadNotification(start_ts, end_ts, lastReadTS);
  const ref = useRef();

  const trackVideoEvents = () => {
    const whatsNew = id && id.length >= 9 && id.substring(0, 9) === 'whats-new';

    tracking.trackEvent(
      window.rzpQ.merchantActions().success('dashboard.notification_section.card.display', {
        trackingID: id,
        video_url,
        ...(whatsNew && { whats_new: true }),
      }),
    );
  };

  const onYouTubePlayer = () => {
    const onPlayerStateChange = (event) => {
      if (event.data == window.YT.PlayerState.PLAYING) {
        trackVideoEvents();
      }
    };

    const youtubeVideoID = getYoutubeVideoID(video_url);
    const videoId = youtubeVideoID === false ? video_url?.split('/')?.slice(-1)[0] : youtubeVideoID;

    // eslint-disable-next-line no-unused-vars
    const player = new window.YT.Player(`player-${id}`, {
      videoId,
      events: {
        onStateChange: onPlayerStateChange,
      },
    });
  };

  useEffect(() => {
    if (video_url && video_url.length && linkFromSource(video_url, 'youtube')) {
      if (typeof window.YT == 'undefined' || typeof window.YT.Player == 'undefined') {
        window.onYouTubePlayerAPIReady = () => {
          onYouTubePlayer();
        };
      } else {
        onYouTubePlayer();
      }
    }
    if (isElementXPercentInViewport(ref.current, 75, 116)) {
      const eventName = 'dashboard.click.notification.card.viewed';
      const notificationData = {
        id,
        ...notification,
      };

      const payload = {
        trackingID: id,
        position: index + 1,
        ...getNotificationTrackingProperties(notificationData, eventName),
      };

      tracking.trackEvent(window.rzpQ.merchantActions().success(eventName, payload));
    } else addOwnRef(ref, index + 1, { id, ...(notification || {}) });
  }, []);

  const handleCTAClick = (e, btn, urlPath, isExternal, id) => {
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
    trackEvents(btn.label, urlPath, btn.type, id, notification);
    if (!(isExternal || btn.id === 'announcement-details-l2')) emptySliderStack();
    // distinguish between links and buttons that open modals
    if (btn.id === 'announcement-details-l2') {
      e.preventDefault();
      const urlParts = btn.url.split('/');
      const urlPartsLength = urlParts.length;
      let notifID = null;
      if (urlPartsLength) {
        if (urlParts[urlPartsLength - 1].length) notifID = urlParts[urlPartsLength - 1];
        else notifID = urlParts[urlPartsLength - 2].length;
        notifID = urlParts[urlPartsLength - 2];
      }
      if (notifID)
        pushSlider({
          component: (
            <Suspense fallback={<Loader />}>
              <WhatsNewDetailsPage id={notifID} lazy />
            </Suspense>
          ),
        });
    } else if (btn.id) {
      e.preventDefault();
      onCTAClick({
        id: btn?.id,
        url: btn?.url,
        type: btn?.sub_asset?.type,
        variant: btn?.sub_asset?.variant,
        handler: btn?.handler,
        history,
        tracking_id: id,
      });
    }
  };

  return (
    <div
      className={classList(
        'NotificationCard',
        isUnread ? 'active' : 'inactive', // Notification is not read and also not expiry
      )}
      ref={ref}
      id={id}
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
            <p>{title}</p>
          </div>
          {secondary_icon && secondary_icon.length ? (
            <span className="NotificationCard-icon--secondary">
              {iconMap[icon] ? (
                <i className={`ico i ${iconMap[secondary_icon]}`} />
              ) : (
                <span className="ico">
                  <img src={secondary_icon} />
                </span>
              )}
            </span>
          ) : null}
        </div>
        {image_url?.length ? (
          <div className="whats-new__video-small">
            <img src={image_url} />
          </div>
        ) : null}
        {video_url && video_url.length ? (
          linkFromSource(video_url, 'youtube') ? (
            <div id={`player-${id}`} className="whats-new__video-small">
              <iframe src={video_url} frameBorder="0" allowFullScreen title={title} />
            </div>
          ) : (
            <video controls className="whats-new__video-small" onPlay={trackVideoEvents}>
              <source src={video_url} type="video/mp4" />
            </video>
          )
        ) : null}
        <div className="description">{description}</div>
        <div className="action-buttons">
          {buttons?.map((btn, idx) => {
            let urlPath = '';
            let isExternal = false;
            if (btn?.url) {
              isExternal = /^http(s)?:\/\//.test(btn.url);
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
              urlPath = isExternal ? URL : internalUrl;
            }
            return (
              <a
                key={idx}
                className={classList('btn', getButtonClass(btn.type))}
                onClick={(e) => handleCTAClick(e, btn, urlPath, isExternal, id)}
                href={urlPath}
                target={isExternal ? '_blank' : ''}
                rel="noreferrer noopener"
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
        ...state.growthService.announcements,
      };
    },
    {
      openModal: openModalx,
      closeModal: closeModalx,
      showAcceptPaymentsModal,
      pushSlider: pushSliderx,
      emptySliderStack: emptySliderStackx,
      fetchAnnouncements,
      setActivePageName: fnSetActivePageName,
      setBaseLocation: fnSetBaseLocation,
    },
  ),
  rTracking(() => window.rzpQ.component('WhatsNew')),
  withRouter,
)(WhatsNew);
