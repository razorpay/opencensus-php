import { useRef, Fragment } from 'react';
import Button from 'common/new-ui/Button';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import SwitchField from 'common/ui/Forms/SwitchField';
import FeeConfiguration from 'merchant/views/MagicCheckout/common/components/FeeConfiguration';
import { getCustomURL } from 'merchant/components/DocsLink';
import { DEFAULT_RULE, MAGIC_DOC_LINK } from 'merchant/views/MagicCheckout/constants';
import 'merchant/views/PaymentPages/PaymentPages/components/Modals/MagicCheckout/style.styl';

const MagicSettingsModal = ({
  isMagicCheckoutEnabled,
  toggleMagicCheckout,
  closeModal,
  magicFeeRule,
  updateRule,
  handleSubmit,
  isEditPaymentPage,
}) => {
  const initialMagicCheckoutEnabledStatus = useRef(isMagicCheckoutEnabled);
  /*
   * For new payment page creation, we are enabling save btn for the scenario
   * of when magic checkout is initially disabled and then enabled.
   */
  const disableSaveBtn =
    !isMagicCheckoutEnabled && !isEditPaymentPage && !initialMagicCheckoutEnabledStatus.current;

  return (
    <ModalMask>
      <Modal className="modal magic-settings-modal" showCloseBtn={false}>
        <div className="header display-flex">
          <i className="i i-magic-checkout" />
          <p className="header-text">Magic Checkout Settings</p>
        </div>
        <div className="body">
          <div className="magic-switch display-flex">
            <div className="switch-label">
              Magic Checkout
              <span className="new-label">New</span>
            </div>
            <div className="display-flex setting-toggle">
              <SwitchField
                onChange={toggleMagicCheckout}
                checked={isMagicCheckoutEnabled}
                type="prime"
              />
              {isMagicCheckoutEnabled ? (
                <b className="text-primary toggle-status">On</b>
              ) : (
                <b className="text-faded toggle-status">Off</b>
              )}
            </div>
          </div>
          <div className="content">
            Increase conversions with a quicker and simpler checkout on your payment page.
            <a
              href={getCustomURL(MAGIC_DOC_LINK)} // nosemgrep: typescript.react.security.audit.react-href-var.react-href-var
              target="_blank"
              rel="noreferrer noopener"
              className="pointer know-more-label"
            >
              Know More <i className="i i-external-redirect" />
            </a>
          </div>
          {isMagicCheckoutEnabled ? (
            <Fragment>
              <hr className="separator" />
              <div>
                <FeeConfiguration
                  required={false}
                  type={DEFAULT_RULE}
                  updateUserFeeRule={updateRule}
                  feeRule={magicFeeRule}
                  isPaymentPage
                />
              </div>
            </Fragment>
          ) : (
            <div className="note">
              We’ll collect customers’ delivery details during checkout along with the payment for
              this page.
            </div>
          )}
        </div>
        <div className="btn-section display-flex">
          <div onClick={closeModal} className="sec-btn">
            Cancel
          </div>
          <Button.Primary
            type="button"
            onClick={handleSubmit}
            disabled={disableSaveBtn}
            className="succes-mtu-btn"
          >
            Save
          </Button.Primary>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default MagicSettingsModal;
