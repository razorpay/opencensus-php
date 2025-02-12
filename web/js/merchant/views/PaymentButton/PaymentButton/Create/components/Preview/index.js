import React from 'react';
import { connect } from 'react-redux';

import { loadColorJs } from 'common/utils/color';
import { classList } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import { updateBrandColorContrast } from 'merchant/reducers/config';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

import AmountDetailsPreview from './Types/AmountDetailsPreview';
import ButtonDetailsPreview from './Types/ButtonDetailsPreview';
import CustomerDetailsPreview from './Types/CustomerDetailsPreview';

class Preview extends React.Component {
  totalTabs = this.isQuickPayTemplate ? 3 : 4;

  componentDidMount() {
    loadColorJs(
      () => this.updateBrandColorContrast(),
      () => this.updateBrandColorContrast(),
    );
  }

  updateBrandColorContrast() {
    const isBrandColorDark = window.colorLib ? window.colorLib.isDark(this.brandColor) : false;

    this.props.updateBrandColorContrast(isBrandColorDark);
  }

  get brandColor() {
    return this.props.config.config.brand_color;
  }

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  render() {
    const { activeTabIndex } = this.props;
    let content;

    if (activeTabIndex === 0) {
      content = <ButtonDetailsPreview {...this.props} />;
    } else if (this.totalTabs === 3) {
      if (activeTabIndex === 1) {
        content = <CustomerDetailsPreview {...this.props} />;
      }
    } else if (this.totalTabs === 4) {
      if (activeTabIndex === 1) {
        content = <AmountDetailsPreview {...this.props} />;
      } else if (activeTabIndex === 2) {
        content = <CustomerDetailsPreview {...this.props} />;
      }
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
            href="https://razorpay.com/docs/payment-button/"
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
    user: state.session.user,
    config: state.config,
  }),
  {
    updateBrandColorContrast,
  },
)(Preview);
