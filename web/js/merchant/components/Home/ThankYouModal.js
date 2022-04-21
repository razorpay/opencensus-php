import React from 'react';
import {
  ModalBody,
  ModalHeader,
  ModalFooter,
  CloseIconContainer,
} from 'common/components/Modal/Styled';
import Button from '@razorpay/blade-old/src/atoms/Button';

const ThankYouModal = ({ handleClose, config = {}, showCloseBtn = true, imgSrc }) => {
  return (
    <div className="thank-you-modal">
      {showCloseBtn && (
        <>
          <CloseIconContainer data-testid="modalCloseButton" onClick={handleClose}>
            <Button
              variant="tertiary"
              size="small"
              variantColor="shade"
              icon="close"
              type="button"
            />
          </CloseIconContainer>
          <ModalHeader>&nbsp;</ModalHeader>
        </>
      )}
      {imgSrc && (
        <div className="img-container">
          <img src={imgSrc} alt="contact-support" />
        </div>
      )}
      <ModalHeader>{config?.headerText}</ModalHeader>
      <ModalBody>{config?.bodyText}</ModalBody>
      <ModalFooter>
        {config?.primaryCtaText && <Button onClick={handleClose}>{config?.primaryCtaText}</Button>}
      </ModalFooter>
    </div>
  );
};

export default ThankYouModal;
