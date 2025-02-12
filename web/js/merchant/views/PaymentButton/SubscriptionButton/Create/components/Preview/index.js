import React from 'react';
import { connect } from 'react-redux';

import { loadColorJs } from 'common/utils/color';
import { classList } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import { updateBrandColorContrast } from 'merchant/reducers/config';

import CustomerDetailsPreview from './Types/CustomerDetailsPreview';
import WidgetPreview from './Types/WidgetPreview';
import { totalTabs } from '../Form';

class Preview extends React.Component {
  totalTabs = totalTabs;

  componentDidMount() {
    loadColorJs(
      () => this.updateBrandColorContrast(),
      () => this.updateBrandColorContrast(),
    );
  }

  updateBrandColorContrast() {
    const isBrandColorDark = window.colorLib ? window.colorLib.isDark(this.brandColor) : false;

    this.initTheme(this.brandColor, isBrandColorDark);

    this.props.updateBrandColorContrast(isBrandColorDark);
  }

  initTheme(themeClr, isBrandColorDark) {
    const textClr = isBrandColorDark ? '#fff' : 'rgba(0, 0, 0, 0.85)';

    const styles = `
		:root {
			--theme-color: ${themeClr};
			--theme-contrast-color: ${textClr};
		}
	`;

    this.appendStyles(styles);
  }

  appendStyles(css) {
    const head = document.head || document.getElementsByTagName('head')[0];

    let style = document.getElementById('preset_style');

    if (!style) {
      style = document.createElement('style');
      style.setAttribute('id', 'preset_style');
      style.type = 'text/css';
    }

    style.appendChild(document.createTextNode(css));

    head.appendChild(style);
  }

  get brandColor() {
    return this.props.config.config.brand_color;
  }

  render() {
    const { activeTabIndex, subscriptionButtonEntity, udfFields, paymentFields } = this.props;
    let content;

    if (!subscriptionButtonEntity) {
      return null;
    }

    if (activeTabIndex === 0 || activeTabIndex === 1) {
      content = <WidgetPreview {...this.props} />;
    } else if (activeTabIndex === 2) {
      content = <WidgetPreview {...this.props} showOneTimePayments />;
    } else if (activeTabIndex === 3) {
      content = <CustomerDetailsPreview {...this.props} />;
    }

    // If content not defined here => Full screen preview is shown, like in ReviewAndCreate component

    return (
      <div
        className={classList(
          'PaymentButton-Create-Preview',
          !content && 'PaymentButton-Create-Preview--hide',
        )}
      >
        <div className="Preview-title">Preview</div>
        {content}

        <div className="GuideBox">
          <div className="title">
            {/* TODO: Compress the icons svg file */}
            <i className="i i-help-outline" />
            Need help
          </div>

          {/*
            <div className="see-video-btn">
              TODO: For i-play compress the svg file
              See video guide for button <i className="i i-play" />
            </div>
          */}

          <DocLink
            className="doc-link"
            target="_blank"
            href="https://razorpay.com/docs/payment-button/subscription-buttons/"
          >
            Visit our Documentation <i className="i i-external-link" />
          </DocLink>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    config: state.config,
  }),
  {
    updateBrandColorContrast,
  },
)(Preview);
