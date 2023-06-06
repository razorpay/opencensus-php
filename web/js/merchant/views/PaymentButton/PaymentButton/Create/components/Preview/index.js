import { connect } from 'react-redux';
import React from 'react';
import CustomerDetailsPreview from './Types/CustomerDetailsPreview';
import AmountDetailsPreview from './Types/AmountDetailsPreview';
import ButtonDetailsPreview from './Types/ButtonDetailsPreview';
import { DocLink } from 'merchant/components/DocsLink';
import { loadColorJs } from 'common/utils/color';
import { classList } from 'common/utils/rzp-utils';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

import { updateBrandColorContrast } from 'merchant/reducers/config';

@connect(
  (state) => ({
    user: state.session.user,
    config: state.config,
  }),
  {
    updateBrandColorContrast,
  },
)
export default class Preview extends React.Component {
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
        class={classList(
          'PaymentButton-Create-Preview',
          !content && 'PaymentButton-Create-Preview--hide',
        )}
      >
        <div class="Preview-title">Preview</div>
        {content}

        <div class="GuideBox">
          <div class="title">
            {/* TODO: Compress the icons svg file */}
            <i class="i i-help-outline" />
            Need help
          </div>

          {/*
            <div class="see-video-btn">
              TODO: For i-play compress the svg file
              See video guide for button <i class="i i-play" />
            </div>
          */}

          <DocLink
            class="doc-link"
            target="_blank"
            href="https://razorpay.com/docs/payment-button/"
          >
            Visit our Documentation <i class="i i-external-link" />
          </DocLink>
        </div>
      </div>
    );
  }
}
