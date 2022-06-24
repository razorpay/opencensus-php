import { useRef } from 'react';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { DISABLE_MAGIC_REASONS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const DisableMagicModal = ({
  showModal,
  showForm,
  onClose,
  handleReason,
  handleDescription,
  handleSubmit,
  isFormInValid,
  handleValidDesc,
  isDescRequired,
  showError,
}) => {
  const descEle = useRef(null);

  const handleOnBlur = () => {
    descEle.current.valid();
  };

  return (
    showModal && (
      <ModalMask>
        <Modal className="modal disable-magic-modal" showCloseBtn={false}>
          <div className="header">
            <p>Disable Magic Checkout?</p>
          </div>
          <div className="modal-body">
            <div className="modal-description">
              <p className={showForm ? 'form-content' : 'content'}>
                We are sorry to see you opt out of using Razorpay’s Magic Checkout experience.
                {!showForm && 'Please tell us what went wrong by sharing your feedback.'}
              </p>
              {showForm && (
                <div className="reason-form">
                  <p className="reason-title">Before you go, tell us more</p>
                  <Input.Radio
                    name="reason"
                    options={DISABLE_MAGIC_REASONS}
                    onChange={handleReason}
                    className="c-rule-type"
                    required={true}
                  />
                  <Input.Textarea
                    key="description"
                    onChange={handleDescription}
                    label="Is there anything else you'd like us to know?"
                    name="description"
                    size="large"
                    required={isDescRequired}
                    requiredError="Required"
                    className={`InputGroup--vTop ${showError ? 'desc-invalid' : ''}`}
                    validator={handleValidDesc}
                    mature
                    onBlur={handleOnBlur}
                    ref={descEle}
                  />
                </div>
              )}
              <div className="btn-section">
                <Button.Secondary type="button" onClick={onClose} className="secondary-btn">
                  No, don’t!
                </Button.Secondary>
                <Button.Primary
                  type="button"
                  onClick={handleSubmit}
                  className="succes-mtu-btn"
                  disabled={isFormInValid}
                >
                  Yes, disable
                </Button.Primary>
              </div>
            </div>
          </div>
        </Modal>
      </ModalMask>
    )
  );
};
export default DisableMagicModal;
