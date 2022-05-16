import React, { Fragment, Component, createRef } from 'react';
import PropTypes from 'prop-types';
import RTracking from 'react-tracking';
import { getAssetTrackingProperties } from '../../merchant/models/GrowthService/commonUtils';

export const BANNER_THEMES = {
  primary: {
    colors: {
      dark: '#415bd3',
      light: '#2682ff',
    },
    toString: () => 'primary',
  },
  warning: {
    colors: {
      dark: '#F08A0A',
      light: '#FFC300',
    },
    toString: () => 'warning',
  },
  danger: {
    colors: {
      dark: '#FF583C',
      light: '#FFA96E',
    },
    toString: () => 'danger',
  },
  success: {
    colors: {
      dark: '#108E2F',
      light: '#4DB534',
    },
    toString: () => 'success',
  },
  purply: {
    colors: {
      dark: '#4f0cc4',
      light: '#5209e7',
    },
    toString: () => 'purply',
  },
  burgundy: {
    colors: {
      dark: '#97144D',
      light: '#b62866',
    },
    toString: () => 'purply',
  },
};

class Announcement extends Component {
  constructor(props) {
    super(props);

    this.state = {
      hovered: false,
    };

    this.isPure = props.hasOwnProperty('hidden');

    if (!this.isPure) {
      this.state.hidden = false;
    }

    this.handleClose = this.handleClose.bind(this);
    this.bannerRef = createRef();
    if (window.IntersectionObserver)
      this.observer = new IntersectionObserver(this.checkIfInViewport, { threshold: 1 });
  }

  checkIfInViewport = (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && entry.intersectionRatio === 1)
        this.timeoutID = setTimeout(() => {
          const { card_id, tracking, trackingData } = this.props;
          const title = this.getTitle();
          const payload = trackingData ? { ...trackingData } : { title, card_id };
          const eventName = 'merchant_dashboard.impression_banner';
          tracking?.trackEvent(
            window.rzpQ?.merchantActions().success(eventName, {
              payload,
              ...getAssetTrackingProperties(card_id, trackingData, {}, eventName),
            }),
          );
          if (this.bannerRef?.current) this.observer?.unobserve(this.bannerRef.current);
        }, 5000);
      else if (this.timeoutID) clearTimeout(this.timeoutID);
    });
  };

  componentDidMount() {
    const { card_id, tracking, trackingData } = this.props;
    const bannerContainer = document.getElementById(`announcement-banner-${card_id}`);
    const title = this.getTitle();
    const banner_text = bannerContainer?.querySelector('.content')?.textContent;
    const payload = trackingData ? { ...trackingData } : { title, card_id, banner_text };

    tracking?.trackEvent(
      window.rzpQ?.merchantActions().success('merchant_dashboard.display_banner', payload),
    );
    if (this.bannerRef?.current) this.observer?.observe(this.bannerRef.current);
  }

  componentWillUnmount = () => {
    if (this.bannerRef?.current) this.observer?.unobserve(this.bannerRef.current);
  };

  getTitle = () => {
    const { card_id } = this.props;
    const titleElement = document.getElementById(`announcement-banner-title-${card_id}`);
    return titleElement?.textContent;
  };

  trackBannerClose = () => {
    const { card_id, tracking, trackingData } = this.props;
    const bannerContainer = document.getElementById(`announcement-banner-${card_id}`);
    const title = this.getTitle();
    const banner_text = bannerContainer?.querySelector('.content')?.textContent;
    const payload = trackingData ? { ...trackingData } : { title, card_id, banner_text };
    const eventName = 'merchant_dashboard.banner_close';
    tracking?.trackEvent(
      window.rzpQ?.merchantActions().success(eventName, {
        payload,
        ...getAssetTrackingProperties(card_id, trackingData, {}, eventName),
      }),
    );
  };

  trackOnHover = () => {
    const { card_id, tracking, trackingData } = this.props;
    const title = this.getTitle();
    this.setState({
      hovered: true,
    });
    const payload = trackingData ? { ...trackingData } : { title, card_id };
    const eventName = 'merchant_dashboard.hover_banner';
    tracking.trackEvent(
      window.rzpQ?.merchantActions().success(eventName, {
        payload,
        ...getAssetTrackingProperties(card_id, trackingData, {}, eventName),
      }),
    );
  };

  handleClose() {
    if (!this.isPure) {
      return this.setState(
        {
          hidden: true,
        },
        () => {
          this.trackBannerClose();
          return this.props.onClose && this.props.onClose();
        },
      );
    }

    this.trackBannerClose();
    return this.props.onClose && this.props.onClose();
  }

  handleCtaClick = (e) => {
    const node = e.target?.nodeName;
    const parentNode = e.target?.parentElement?.nodeName;

    // the condition after && is because some CTA text are wrapped in strong, b, etc. tags, so checking if their parent is a or button, then fire an event.
    if (node !== 'A' && node !== 'BUTTON' && parentNode !== 'A' && parentNode !== 'BUTTON') return;

    const { card_id, tracking, trackingData } = this.props;
    const link = node === 'A' ? e.target?.href : e.target?.parentElement?.href;
    const title = this.getTitle();
    const banner_text = e.target?.closest('.content')?.textContent;
    const cta_value = e.target?.textContent?.trim();
    const payload = trackingData ? { ...trackingData } : { title, card_id, banner_text };

    tracking?.trackEvent(
      window.rzpQ?.merchantActions().initiated('merchant_dashboard.click_banner_cta', {
        ...payload,
        link,
        cta_value,
      }),
    );
  };

  render() {
    const {
      title,
      theme: passedTheme,
      className,
      hidden,
      onClose,
      fullPage,
      card_id = '',
      ...props
    } = this.props;
    const theme = BANNER_THEMES[passedTheme];

    if (this.isPure ? hidden : this.state.hidden) {
      return null;
    }

    props.className = `announcement-banner${className ? ` ${className}` : ''} ${
      fullPage ? 'announcement-banner--fullpage' : ''
    }`;

    const { dark, light } = theme.colors;
    const titleStyle = {
      color: light,
    };
    const titleContentStyle = {
      backgroundImage: `linear-gradient(90deg, ${dark} 0%, ${dark} 50%, ${light} 100%)`,
    };

    return (
      <div
        {...props}
        onClick={this.handleCtaClick}
        id={`announcement-banner-${card_id}`}
        onMouseEnter={this.state.hovered ? null : this.trackOnHover}
        ref={this.bannerRef}
      >
        {title && !fullPage && (
          <div className="title" style={titleStyle}>
            <div className="title-content" style={titleContentStyle}>
              <div id={`announcement-banner-title-${card_id}`} className="title-content-wrapper">
                {title}
              </div>
            </div>
          </div>
        )}

        {fullPage && (
          <Fragment>
            <div class="skew-pattern skew-pattern-left" style={titleStyle} />
            <div class="skew-pattern skew-pattern-right" style={titleStyle} />
          </Fragment>
        )}

        <div className="content">{this.props.children}</div>
        {onClose && (
          <div class="close-btn" onClick={this.handleClose}>
            &times;
          </div>
        )}
      </div>
    );
  }
}

Announcement.defaultProps = {
  theme: BANNER_THEMES.primary,
  title: null,
};

Announcement.propTypes = {
  theme: PropTypes.oneOf(Object.keys(BANNER_THEMES)),
  title: PropTypes.string,
};

// eslint-disable-next-line babel/new-cap
export default RTracking(() => window.rzpQ.component('DashboardBanner'))(Announcement);
