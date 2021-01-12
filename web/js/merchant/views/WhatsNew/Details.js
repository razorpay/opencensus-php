import React from 'react';
import RTracking from 'react-tracking';
import { withRouter, Link } from 'react-router-dom';
import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';

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

@withRouter
@RTracking(() => window.rzpQ.component('AnnouncementDetails'))
export default class AnnouncementDetails extends React.Component {
  state = {
    hasScrolledToEnd: false,
  };

  componentDidMount() {
    const { id, tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('dashboard.notification_section.card.l2.display', {
        card_id: id,
        title: window.notifications.find((notification) => notification.id === id).l2_content.title,
        whats_new: isWhatsNewSection(id),
      }),
    );
  }

  onButtonClick = (button, index) => () => {
    const isExternal = /^http(s)?:\/\//.test(button.url);
    const isHash = !isExternal && button.url.indexOf('#') === 0;
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
        }),
    );

    window.open(urlPath, isExternal ? '_blank' : '_self');
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
          }),
      );

      this.setState({
        hasScrolledToEnd: true,
      });
    }
  }, 20);

  render() {
    const { closeUrl, id: notificationId } = this.props;
    const { buttons, title, content } = window.notifications.find(
      (notification) => notification.id === notificationId,
    ).l2_content;

    return (
      <div className="content-wrapper content-sm announcement-details">
        <div className="panel panel-default SliderPanel announcement-details__container">
          <div className="panel-heading">
            <div className="heading-content">
              <b>Announcements</b>
            </div>
          </div>
          <div
            className="panel-body announcement-details__content"
            onScroll={({ target }) => {
              this.handleContentScroll(target, notificationId, title);
            }}
          >
            {closeUrl ? (
              <Link to={closeUrl} onClick={this.handleBackButtonClick}>
                <i className="i i-chevron-left"></i> Back
              </Link>
            ) : null}
            <div dangerouslySetInnerHTML={{ __html: content }} />
          </div>
          <div className="announcement-details__footer action-buttons">
            {this.renderActionButtons(
              buttons.map((button) => ({
                ...button,
                notificationId,
              })),
            )}
          </div>
        </div>
      </div>
    );
  }
}
