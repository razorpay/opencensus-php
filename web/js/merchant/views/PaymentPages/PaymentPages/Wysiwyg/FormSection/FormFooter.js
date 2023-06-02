import React from 'react';
import CreatorModal from './CreatorModal';
import EditLayer from 'merchant/views/PaymentPages/PaymentPages/components/EditLayer';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import { classList } from 'common/utils/rzp-utils';
import { getCurrency } from 'common/ui/Amount';

export default class FormFooter extends React.PureComponent {
  state = {
    isEditModalOpened: false,
    paymentButtonLabel: this.props.paymentButtonLabel,
    disableSubmit: false,
  };

  componentDidUpdate(prevProps, prevState) {
    if (
      prevProps.paymentButtonLabel !== this.props.paymentButtonLabel ||
      prevState.isEditModalOpened !== this.state.isEditModalOpened
    ) {
      this.setState({
        paymentButtonLabel: this.props.paymentButtonLabel,
      });
    }
  }

  toggleModal = (force) => {
    this.setState((prevState) => ({
      isEditModalOpened: typeof force !== 'undefined' ? force : !prevState.paymentButtonLabel,
    }));
  };

  onChangePaymentButtonLabel = (e) => {
    this.setState(
      {
        paymentButtonLabel: e.target.value,
      },
      (_) => {
        const disableSubmit =
          !!this.formFooter.querySelectorAll('.is-invalid').length ||
          // eslint-disable-next-line react/no-access-state-in-setstate
          !this.state.paymentButtonLabel;

        this.setState({
          disableSubmit,
        });
      },
    );
  };

  savePaymentButtonLabel = () => {
    this.props.updateData({
      settings: {
        payment_button_label: this.state.paymentButtonLabel,
      },
    });

    this.toggleModal(false);
  };

  setRef = (el) => (this.formFooter = el);

  render() {
    const { currency, isListSorting, securityBrandingLogo } = this.props;
    const { isEditModalOpened, paymentButtonLabel, disableSubmit } = this.state;

    const content = (
      <div class="form-footer-payment">
        <img id="fin-logo" alt="pay-methods" src={securityBrandingLogo} />

        <button class="btn btn-gradient">
          {isEditModalOpened ? paymentButtonLabel : this.props.paymentButtonLabel}{' '}
          <span style={{ marginLeft: 4 }}>
            <b class="currency-symbol">{getCurrency(currency).symbol}</b> 000.00
          </span>
        </button>
      </div>
    );

    return (
      <div id="form-footer" ref={this.setRef}>
        {isEditModalOpened && (
          <CreatorModal class="CreatorModal-BaseForm" overElement allowScroll>
            <div>
              <Input
                name="payment_button_label"
                required
                maxLength="16"
                pattern="^[0-9a-zA-Z ]+"
                label="Payment Button Label"
                value={paymentButtonLabel}
                onChange={this.onChangePaymentButtonLabel}
                autoFocus
              />
              {content}
            </div>

            <Button.Transparent
              class="base-form-side-btn base-form-cancel"
              type="button"
              onClick={(_) => this.toggleModal(false)}
            >
              <span>&times;</span>
              Cancel
            </Button.Transparent>

            <Button.Transparent
              class="base-form-side-btn base-form-save"
              type="button"
              disabled={disableSubmit}
              onClick={this.savePaymentButtonLabel}
            >
              <span class="icon i-check" />
              Save
            </Button.Transparent>
          </CreatorModal>
        )}

        <EditLayer
          class={classList('edit-layer--formFooter', isListSorting && 'disable-hover')}
          onClick={(_) => this.toggleModal(true)}
        >
          {content}
          <i class="i i-edit" />
        </EditLayer>
      </div>
    );
  }
}
