import React, { Fragment, Component } from 'react';
import PropTypes from 'prop-types';

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

  handleClose() {
    if (!this.isPure) {
      return this.setState(
        {
          hidden: true,
        },
        () => {
          return this.props.onClose && this.props.onClose();
        }
      );
    }

    return this.props.onClose && this.props.onClose();
  }

  render() {
    const {
        title,
        theme: passedTheme,
        className,
        hidden,
        onClose,
        fullPage,
        ...props
      } = this.props,
      theme = BANNER_THEMES[passedTheme];

    if (this.isPure ? hidden : this.state.hidden) {
      return null;
    }

    props.className = `announcement-banner${className ? ` ${className}` : ''} ${fullPage ? 'announcement-banner--fullpage' : ''}`;

    const { dark, light } = theme.colors,
      titleStyle = {
        color: light,
      },
      titleContentStyle = {
        backgroundImage: `linear-gradient(90deg, ${dark} 0%, ${dark} 50%, ${light} 100%)`,
      };

    return (
      <div {...props}>
        {title && !fullPage && (
          <Fragment>
            <div className="title" style={titleStyle}>
              <div className="title-content" style={titleContentStyle}>
                <div className="title-content-wrapper">{title}</div>
              </div>
            </div>
          </Fragment>
        )}

        {
          fullPage && (
            <Fragment>
              <div class="skew-pattern skew-pattern-left" style={titleStyle}></div>
              <div class="skew-pattern skew-pattern-right" style={titleStyle}></div>
            </Fragment>
          )
        }

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
