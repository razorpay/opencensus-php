import React, { Fragment, Component } from 'react';
import PropTypes from 'prop-types';
import RTracking from 'react-tracking';

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
};

@RTracking(() => window.rzpQ.component('DashboardBanner'))
export default class Announcement extends Component {
  constructor(props) {
    super(props);

    this.isPure = props.hasOwnProperty('hidden');

    if (!this.isPure) {
      this.state = {
        hidden: false,
      };
    }

    this.handleClose = this.handleClose.bind(this);
  }

  componentDidMount() {
    const { title, card_id, tracking } = this.props;
    const bannerContainer = document.getElementById(`announcement-banner-${card_id}`);

    tracking?.trackEvent(
      window.rzpQ?.merchantActions().success('merchant_dashboard.display_banner', {
        title,
        banner_text: bannerContainer?.querySelector('.content')?.textContent,
        card_id,
      }),
    );
  }

  trackBannerClose = () => {
    const { title, card_id, tracking } = this.props;
    const bannerContainer = document.getElementById(`announcement-banner-${card_id}`);

    tracking?.trackEvent(
      window.rzpQ?.merchantActions().success('merchant_dashboard.banner_close', {
        title,
        banner_text: bannerContainer?.querySelector('.content')?.textContent,
        card_id,
      }),
    );
  }

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
    if ((node !== 'A' && node !== 'BUTTON') && (parentNode !== 'A' && parentNode !== 'BUTTON')) return;

    const { title, card_id, tracking } = this.props;
    const link = node === 'A' ? e.target?.href : e.target?.parentElement?.href;

    tracking?.trackEvent(
      window.rzpQ?.merchantActions().initiated('merchant_dashboard.click_banner_cta', {
        title,
        banner_text: e.target?.closest('.content')?.textContent,
        card_id,
        cta_value: e.target?.textContent?.trim(),
        link
      }),
    );
  }

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
      } = this.props,
      theme = BANNER_THEMES[passedTheme];

    if (this.isPure ? hidden : this.state.hidden) {
      return null;
    }

    props.className = `announcement-banner${className ? ` ${className}` : ''} ${
      fullPage ? 'announcement-banner--fullpage' : ''
    }`;

    const { dark, light } = theme.colors,
      titleStyle = {
        color: light,
      },
      titleContentStyle = {
        backgroundImage: `linear-gradient(90deg, ${dark} 0%, ${dark} 50%, ${light} 100%)`,
      };

    return (
      <div {...props} onClick={this.handleCtaClick} id={`announcement-banner-${card_id}`}>
        {title && !fullPage && (
          <Fragment>
            <div className="title" style={titleStyle}>
              <div className="title-content" style={titleContentStyle}>
                <div className="title-content-wrapper">{title}</div>
              </div>
            </div>
          </Fragment>
        )}

        {fullPage && (
          <Fragment>
            <div class="skew-pattern skew-pattern-left" style={titleStyle}></div>
            <div class="skew-pattern skew-pattern-right" style={titleStyle}></div>
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
};
