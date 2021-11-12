import React, { useState, useEffect } from 'react';
import { NSSModalCommonProps } from './TypeDeclare/XCATypeDeclare';
import {
  BUSINESS_TYPE_DETAILS,
  BUSINESS_CATEGORY_DETAILS,
  VALID_PINCODE_DIGIT,
  URLS,
} from './constant/NSSFormConstants';
import Button from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { getXBaseURL } from './common/utils';
import { nitroCampaignId } from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import XStyleDropdown from './common/XStyleDropdown';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { compose } from 'redux';
import XCATooltip from './common/XCAtoolTip';

const NitroSelfServeForms = ({
  setShowState,
  user,
  handleClose,
  showNotification,
  tracking,
}: NSSModalCommonProps): React.ReactElement => {
  const userBusinessOperationPin = user.business_operation_pin || '      ';
  const userBusinessType = BUSINESS_TYPE_DETAILS.ID_TO_VALUE_MAP[user.business_type] || '';
  const userBusinessCategory =
    BUSINESS_CATEGORY_DETAILS.ID_TO_VALUE_MAP[user.business_category] || '';
  const [formState, setFormState] = useState<'form' | 'processing' | 'error'>('form');
  const [businessOperationPin, setBusinessOperationPin] = useState<string>(
    userBusinessOperationPin,
  );
  const [bOPinArray, setBOPinArray] = useState<Array<string>>(userBusinessOperationPin.split(''));
  const [businessType, setBusinessType] = useState<string>(userBusinessType);
  const [businessCategory, setBusinessCategory] = useState<string>(userBusinessCategory);
  const [isProceedDisabled, setProceedDisabled] = useState(true);
  let allocatedBank: string | null = null;

  const handlePinInputDown = (
    event: React.KeyboardEvent<HTMLInputElement>,
    index: number,
  ): void => {
    const key = event.key;
    const target = event.target as HTMLInputElement;
    if (!(VALID_PINCODE_DIGIT.test(key) || key === 'Backspace')) return;
    const value = key === 'Backspace' ? '' : key;
    const newBOPinArray = [...bOPinArray];
    newBOPinArray[index] = value;
    setBOPinArray(newBOPinArray);
    setBusinessOperationPin(newBOPinArray.join(''));
    const targetValue = target.value;
    if (key === 'Backspace' && targetValue.length === 0) {
      const prevInputElement = target.previousElementSibling;
      if (prevInputElement) (prevInputElement as HTMLElement).focus();
      else target.blur();
    }
    if (value.length === 0) return;
    const nextInputElement = target.nextElementSibling;
    if (nextInputElement) (nextInputElement as HTMLElement).focus();
    else target.blur();
  };

  const handleSubmit = (): void => {
    setFormState('processing');
    tracking.trackEvent(
      window.rzpQ?.merchantActions().clicked('nitro_neostone.eligbility_proceed', {
        pin_code: businessOperationPin,
        business_type: businessType,
        business_category: businessCategory,
      }),
    );

    merchantFetch({
      url: URLS.BANK_ALLOCATION,
      mode: 'live',
      method: 'post',
      data: {
        allocation_strategy: 'PROPORTIONAL_SPLIT_ON_TIE_V1',
        pin_code: businessOperationPin,
        business_type: businessType,
        business_category: businessCategory,
      },
    })
      .then((data) => {
        const { allocated_account_type, allocated_bank } = data?.data?.data;
        if (allocated_account_type === 'CA_DIRECT') {
          allocatedBank = allocated_bank;
          merchantFetch({
            url: URLS.BANK_SAVE,
            mode: 'live',
            method: 'post',
            data: [
              {
                allocated_bank: allocatedBank,
                onboarding_flow: 'NITRO',
                campaign_id: nitroCampaignId().version,
              },
            ],
          })
            .then(() => {
              tracking.trackEvent(
                window.rzpQ
                  ?.merchantActions()
                  .success(`nitro_neostone.serviceable_${allocatedBank?.toLowerCase()}`, {
                    pin_code: businessOperationPin,
                    business_type: businessType,
                    business_category: businessCategory,
                    allocated_bank: allocatedBank,
                  }),
              );
              window.open(
                `${getXBaseURL()}/current-account-application/pre-allocated?businessType=${businessType}&businessCategory=${businessCategory}&pincode=${businessOperationPin}`,
                '_blank',
              );
              handleClose();
            })
            .catch((_) => {
              tracking.trackEvent(
                window.rzpQ.merchantActions().failed('nitro_neostone.x_redirect'),
              );

              showNotification({
                type: 'error',
                message: 'An error occurred in connecting to the server',
                hidePrevious: true,
              });
              setFormState('form');
            });
        } else {
          throw new Error();
        }
      })
      .catch((_) => {
        tracking.trackEvent(
          window.rzpQ.merchantActions().failed('nitro_neostone.bank_serviceable'),
        );
        setFormState('error');
      });
  };

  const bOPinDigitList = (
    <div className="nss-form__input-form__input-group--operational-pin__input-pin-list">
      {bOPinArray.map((pinDigit, index) => (
        <input
          key={`business_operation_pin_${index}`}
          value={pinDigit}
          className="nss-form__input-form__input-group--operational-pin__input-pin-list__input-pin"
          type="number"
          onKeyDown={(event) => handlePinInputDown(event, index)}
        />
      ))}
    </div>
  );

  useEffect(() => {
    if (
      businessType?.length &&
      businessCategory?.length &&
      bOPinArray.every((digit) => VALID_PINCODE_DIGIT.test(digit))
    )
      setProceedDisabled(false);
    else setProceedDisabled(true);
  }, [businessType, businessCategory, bOPinArray]);

  let contentToShow: React.ReactElement | null = null;
  if (formState === 'processing') {
    contentToShow = (
      <div className="loader-content">
        <div className="nss-loader">
          <img src="https://x.razorpay.com/dist/assets/img/x-loader-sprite.png" />
        </div>
      </div>
    );
  } else if (formState === 'error') {
    contentToShow = (
      <div className="nss-error">
        <div className="error-content">
          <div className="error-content__image">
            <img src="https://x.razorpay.com/dist/assets/img/unhappy.svg" />
          </div>
          <div className="error-content__title">Sorry, we currently cannot service you</div>
          <div className="error-content__desc">
            We have taken your request. We will get in touch with you as soon as we can service your
            business and location.
          </div>
          <Button.Primary type="button" class="btn btn-primary submit-btn" onClick={handleClose}>
            Okay
          </Button.Primary>
        </div>
      </div>
    );
  } else {
    contentToShow = (
      <div className="nss-form">
        <div className="nss-form__title">First, help us get to know your business better 😇</div>
        <form className="nss-form__input-form">
          <div className="nss-form__input-form__input-group">
            <XStyleDropdown
              label="Business Type"
              currentValue={businessType}
              itemArray={BUSINESS_TYPE_DETAILS.VALUE_NAME_PAIRS}
              changeFunction={setBusinessType}
            />
            <XStyleDropdown
              label="Business Category"
              currentValue={businessCategory}
              itemArray={BUSINESS_CATEGORY_DETAILS.VALUE_NAME_PAIRS}
              changeFunction={setBusinessCategory}
            />
          </div>
          <div className="nss-form__input-form__input-group--operational-pin">
            <div className="nss-form__input-form__input-group--operational-pin__input">
              <label htmlFor="business_operation_pin">
                Operational Office Pincode <XCATooltip formVersion />
              </label>
              {bOPinDigitList}
            </div>
            <div className="nss-form__input-form__input-group--operational-pin__desc">
              📍Have multiple offices? Enter pincode of any operational address.
            </div>
          </div>
          <div className="nss-form__input-form__button-group">
            <Button.Transparent
              onClick={() => {
                setShowState('details');
              }}
            >
              Go Back
            </Button.Transparent>
            <Button.Primary
              type="submit"
              class="btn btn-primary submit-btn"
              iconAfter="arrow-forward"
              onClick={handleSubmit}
              disabled={isProceedDisabled}
            >
              Proceed
            </Button.Primary>
          </div>
        </form>
      </div>
    );
  }

  return contentToShow;
};

export default compose(
  connect(null, {
    showNotification: showNotificationProp,
  }),
)(NitroSelfServeForms);
