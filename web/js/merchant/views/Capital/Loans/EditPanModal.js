import React, { useEffect, useState } from 'react';
import { validatePANCard, validatePersonalPAN } from 'common/utils/validators';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import ToggleWithDescription from '../components/ToggleWithDescription';
import Popover, { PopoverBody } from 'common/ui/Popover';

export default function EditPanModal({
  handleSubmit,
  closeModal,
  selected = '',
  availablePans,
  pgLinkedPan,
  parentSelector,
  align = 'top',
}) {
  const [value, setValue] = useState(selected);
  const [isCustomPan, setIsCustomPan] = useState(false);
  const isCustomPanAlreadyPresent = isCustomPan && new Set(availablePans).has(value);
  const error = validatePersonalPAN(value)
    ? 'Please enter a valid personal pan number'
    : isCustomPanAlreadyPresent
    ? 'Already added, please choose from above.'
    : null;

  const onChange = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setValue(event.target.value);
  };

  const isInputInvalid =
    (isCustomPan && !!validatePersonalPAN(value)) || isCustomPanAlreadyPresent || value.length < 10;

  const registerCoApplicantPan = (e) => {
    e.preventDefault();
    handleSubmit(value);
  };
  return (
    <div>
      <ModalHeader title="Change PAN Number" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className="help-block">
          Ensure to enter the alternative PAN number to continue applying.
        </div>
        <form>
          <div className="form-group">
            {availablePans.map((pan) => (
              <ToggleWithDescription
                radioPosition="left"
                disabled={false}
                size="small"
                onClick={() => {
                  setValue(pan);
                  setIsCustomPan(false);
                }}
                selected={value === pan && !isCustomPan}
                title={
                  <div className="flex full-width no-margin p-l">
                    <span>{pan}</span>
                    {pgLinkedPan === pan && (
                      <div className="full-width no-margin text-right">
                        <strong className="text-small text-faded">Linked to our PG</strong>
                        <small className="help-content">
                          &nbsp;
                          <i className="i i-info-outline text-faded" />
                          <Popover align={align} theme="dark" parentQuerySelector={parentSelector}>
                            <PopoverBody>
                              <div className="text-left">
                                This PAN Number is connected with Razorpay Payment Gateway.
                              </div>
                            </PopoverBody>
                          </Popover>
                        </small>
                      </div>
                    )}
                  </div>
                }
              />
            ))}
            <div>
              <ToggleWithDescription
                size="small"
                disabled={false}
                radioPosition="left"
                onClick={() => {
                  setValue('');
                  setIsCustomPan(true);
                }}
                selected={isCustomPan}
                title="Custom"
                description={
                  isCustomPan && (
                    <div className="m-t">
                      <Input
                        key="pan"
                        label=""
                        onChange={onChange}
                        name="pan"
                        className={isInputInvalid ? 'no-margin is-invalid' : 'no-margin'}
                        value={value}
                        autoFocus={true}
                        propagatedError={error}
                        placeholder="ATTPC6453H"
                        required
                      />
                    </div>
                  )
                }
              />
            </div>
          </div>
          <div className="Modal__actions flex">
            <Button type="button" className="btn btn-default btn-block" onClick={closeModal}>
              Cancel
            </Button>
            <Button.Primary
              type="submit"
              className="no-margin btn-block"
              onClick={registerCoApplicantPan}
              disabled={isInputInvalid}
            >
              Confirm
            </Button.Primary>
          </div>
        </form>
      </div>
    </div>
  );
}
