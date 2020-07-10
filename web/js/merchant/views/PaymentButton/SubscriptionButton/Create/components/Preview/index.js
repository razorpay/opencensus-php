import { connect } from 'react-redux';

import CustomerDetailsPreview from './Types/CustomerDetailsPreview';
import WidgetPreview from './Types/WidgetPreview';

import { loadColorJs } from 'common/utils/color';
import { classList } from 'common/utils/rzp-utils';

import { updateBrandColorContrast } from 'merchant/reducers/config';

@connect(
  state => ({
    config: state.config,
  }),
  {
    updateBrandColorContrast,
  }
)
export default class Preview extends React.Component {
  totalTabs = 4;

  componentDidMount() {
    loadColorJs(
      () => this.updateBrandColorContrast(),
      () => this.updateBrandColorContrast()
    );
  }

  updateBrandColorContrast() {
    const isBrandColorDark = window.colorLib
      ? window.colorLib.isDark(this.brandColor)
      : false;

    this.props.updateBrandColorContrast(isBrandColorDark);
  }

  get brandColor() {
    return this.props.config.config.brand_color;
  }

  render() {
    const {
      activeTabIndex,
      subscriptionButtonEntity,
      udfFields,
      planFields,
    } = this.props;
    let content;

    if (!subscriptionButtonEntity) {
      return null;
    }

    if (activeTabIndex === 0 || activeTabIndex === 1) {
      content = <WidgetPreview {...this.props} />;
    } else if (activeTabIndex === 2) {
      content = <CustomerDetailsPreview {...this.props} />;
    }

    // If content not defined here => Full screen preview is shown via ReviewAndCreate component

    return (
      <div
        class={classList(
          'PaymentButton-Create-Preview',
          !content && 'PaymentButton-Create-Preview--hide'
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

          <a
            class="doc-link"
            target="_blank"
            href="https://razorpay.com/docs/payment-button/"
          >
            Visit our Documentation <i class="i i-external-link" />
          </a>
        </div>
      </div>
    );
  }
}
