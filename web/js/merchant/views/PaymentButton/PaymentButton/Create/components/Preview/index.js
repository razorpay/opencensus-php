import { connect } from 'react-redux';

import CustomerDetailsPreview from './Types/CustomerDetailsPreview';
import AmountDetailsPreview from './Types/AmountDetailsPreview';
import ButtonDetailsPreview from './Types/ButtonDetailsPreview';

import { loadColorJs } from 'common/utils/color';
import { classList } from 'common/utils/rzp-utils';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

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
  totalTabs = this.isQuickPayTemplate ? 3 : 4;

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

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  render() {
    const {
      activeTabIndex,
      paymentButtonEntity,
      udfFields,
      amountFields,
    } = this.props;
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
      </div>
    );
  }
}
