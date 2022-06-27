import React, { useMemo } from 'react';

// components
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

// constants
import { NON_3DS_CARDS_ACTIVATION_STATUS } from 'merchant/reducers/non3dsCardsActivation';

/**
 *
 * @param {{ open: Function, onClose: Function }} param0 Props to control the modal
 * @returns {React.ReactNode} Return EnableNon3ds Modal
 */
const Non3dsLearnMore = ({ open, status, onDisable, onEnable, onClose }) => {
  const description = useMemo(() => {
    const handleLinkClick = (e) => {
      e.preventDefault();
      if (status === NON_3DS_CARDS_ACTIVATION_STATUS.DISABLED && onEnable) {
        onEnable();
      }
      if (status === NON_3DS_CARDS_ACTIVATION_STATUS.ENABLED && onDisable) {
        onDisable();
      }
    };
    if (status === NON_3DS_CARDS_ACTIVATION_STATUS.ENABLED) {
      return (
        <p>
          If you don’t want non 3D Secure card support for international transactions, you can{' '}
          <a href="" onClick={handleLinkClick}>
            Disable
          </a>{' '}
          it.
        </p>
      );
    }
    if (status === NON_3DS_CARDS_ACTIVATION_STATUS.DISABLED) {
      return (
        <p>
          Non 3D Secure card support is disabled for your international transactions, if you think,
          this was done by mistake, please request to{' '}
          <a href="" onClick={handleLinkClick}>
            Enable
          </a>{' '}
          it.
        </p>
      );
    }
    return null;
  }, [onDisable, onEnable, status]);

  if (!open) {
    return null;
  }

  return (
    <ModalMask>
      <Modal onClose={onClose} className="non3dsCardsModal">
        <ModalContent>
          <div className="non-3ds-modal-header">
            <h3>Support for Non 3D Secure transactions</h3>
          </div>
          <div class="non-3ds-modal-content">{description}</div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

export default Non3dsLearnMore;
