import React, { useState, useContext, useEffect } from 'react';
import moment from 'moment';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import {
  DateTimeContainer,
  Label,
  CheckboxLabel,
  OptionalText,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponValidityWidegt/styles';
import {
  FormGroup,
  InputIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

//helpers
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import { validateCouponDateTime } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';

interface AccordionBodyProps {
  flow: string;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ flow }) => {
  const { widgetsData, setWidgetsData, errorStates, setErrorStates } = useContext(ModalContext);
  const [startTimeId, setStartTimeId] = useState(0);
  const [endTimeId, setEndTimeId] = useState(0);

  const handleFormValidations = ({ startDate, startTime, endDate, endTime }) => {
    validateCouponDateTime({
      setErrorStates,
      startDateTime: moment(`${startDate} ${startTime}`).toISOString(),
      endDateTime: moment(`${endDate} ${endTime}`).toISOString(),
      isEndDateRequired: widgetsData.couponValidity.isLimitedUseage,
      flowName: flow,
    });
  };

  // this is done consiously, even if the props are changing the component was re-rendering but the timer options was not getting updated in case of Input.TimePicker, so was forced to do this.
  useEffect(() => {
    if (startTimeId === 2) return;
    setStartTimeId((prev) => prev + 1);
  }, [widgetsData.couponValidity.startDate]);

  useEffect(() => {
    if (endTimeId === 2) return;
    setEndTimeId((prev) => prev + 1);
  }, [widgetsData.couponValidity.endDate]);

  return (
    <div>
      <FormGroup>
        <div className="form-label">Active dates</div>
        <div className="form-input">
          <div className="date-time">
            <DateTimeContainer>
              <div className="date">
                <Label>Start date</Label>
                <Input.ToCalendar
                  autoRender
                  data-name="date"
                  placeholder="DD/MM/YYYY"
                  readOnly={true}
                  onChange={(date) => {
                    setWidgetsData(() => ({
                      ...widgetsData,
                      couponValidity: {
                        ...widgetsData.couponValidity,
                        startDate: moment(date).format('YYYY-MM-DD'),
                        endDate: moment(date).add(1, 'days').format('YYYY-MM-DD'),
                      },
                    }));
                    handleFormValidations({
                      ...widgetsData.couponValidity,
                      startDate: moment(date).format('YYYY-MM-DD'),
                    });
                  }}
                  size="half_small"
                  addonAfter={<i className="i i-date-range" />}
                  placement="topLeft"
                  allowToday={true}
                  disablePastDates={true}
                  required
                  value={widgetsData.couponValidity.startDate}
                  disabled={flow === 'edit' && widgetsData.status !== 'created'}
                />
              </div>
              <div className="time">
                <Label>Start time</Label>
                <Input.TimePicker
                  key={startTimeId}
                  placeholder="11:59PM"
                  readOnly={true}
                  onChange={(time) => {
                    setWidgetsData(() => ({
                      ...widgetsData,
                      couponValidity: {
                        ...widgetsData.couponValidity,
                        startTime: moment(time).format('h:mm a'),
                        endTime: moment(time).add(1, 'day').endOf('day').format('h:mm a'),
                      },
                    }));

                    handleFormValidations({
                      ...widgetsData.couponValidity,
                      startTime: moment(time).format('h:mm a'),
                    });
                  }}
                  size="half_small"
                  addonAfter={<i className="i i-time" />}
                  required
                  value={widgetsData.couponValidity.startTime}
                  defaultValue={widgetsData.couponValidity.startTime}
                  disabled={flow === 'edit' && widgetsData.status !== 'created'}
                />
              </div>
            </DateTimeContainer>

            <div style={{ display: 'flex', margin: '16px 0' }}>
              <Input.Check
                checked={widgetsData.couponValidity.isLimitedUseage}
                type="checkbox"
                name="isLimitedUseage"
                onChange={(e) => {
                  setWidgetsData(() => ({
                    ...widgetsData,
                    couponValidity: {
                      ...widgetsData.couponValidity,
                      isLimitedUseage: e.target.checked,
                    },
                  }));

                  setErrorStates((prev) => ({
                    ...prev,
                    couponValidity: {
                      ...prev.couponValidity,
                      couponTime: null,
                    },
                  }));
                }}
                autoRender
              />
              <CheckboxLabel>Set an end date</CheckboxLabel>
            </div>

            {widgetsData.couponValidity.isLimitedUseage ? (
              <DateTimeContainer>
                <div className="date">
                  <Label>End date</Label>
                  <Input.ToCalendar
                    autoRender
                    data-name="date"
                    placeholder="DD/MM/YYYY"
                    readOnly={true}
                    onChange={(date) => {
                      setWidgetsData(() => ({
                        ...widgetsData,
                        couponValidity: {
                          ...widgetsData.couponValidity,
                          endDate: moment(date).format('YYYY-MM-DD'),
                        },
                      }));

                      handleFormValidations({
                        ...widgetsData.couponValidity,
                        endDate: moment(date).format('YYYY-MM-DD'),
                      });
                    }}
                    size="half_small"
                    addonAfter={<i className="i i-date-range" />}
                    placement="topLeft"
                    allowToday={true}
                    disablePastDates={true}
                    required
                    value={widgetsData.couponValidity?.endDate || ''}
                  />
                </div>
                <div className="time">
                  <Label>End time</Label>
                  <Input.TimePicker
                    key={endTimeId}
                    placeholder="11:59PM"
                    readOnly={true}
                    onChange={(time) => {
                      setWidgetsData(() => ({
                        ...widgetsData,
                        couponValidity: {
                          ...widgetsData.couponValidity,
                          endTime: moment(time).format('h:mm a'),
                        },
                      }));

                      handleFormValidations({
                        ...widgetsData.couponValidity,
                        endTime: moment(time).format('h:mm a'),
                      });
                    }}
                    size="half_small"
                    addonAfter={<i className="i i-time" />}
                    value={widgetsData.couponValidity?.endTime || ''}
                    defaultValue={widgetsData.couponValidity?.endTime}
                  />
                </div>
              </DateTimeContainer>
            ) : null}

            <p className="error-message"> {errorStates.couponValidity.couponTime}</p>
          </div>
        </div>
      </FormGroup>

      <FormGroup>
        <div className="form-label">
          Total maximum budget <OptionalText> (optional) </OptionalText>
        </div>
        <div className="form-input">
          <Label>Expire coupon on end date or when the total amount reached is</Label>
          <Input
            name="maxBudget"
            type="number"
            addonBefore={<InputIcon className="i-rupee" />}
            defaultValue={widgetsData.couponValidity.maxBudget}
            value={widgetsData.couponValidity.maxBudget}
            className="w-200"
            onChange={(e) => {
              setWidgetsData(() => ({
                ...widgetsData,
                couponValidity: {
                  ...widgetsData.couponValidity,
                  maxBudget: e.target.value,
                },
              }));
            }}
            onWheel={onWheelPreventChange}
            disabled={flow === 'edit' && widgetsData.status !== 'created'}
          />
        </div>
      </FormGroup>
    </div>
  );
};

interface CouponValidityWidgetProps {
  flow: string;
}

const CouponValidityWidget: React.FC<CouponValidityWidgetProps> = ({ flow }) => {
  const { errorStates } = useContext(ModalContext);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setIsOpen((prev) => {
      const hasErrors =
        errorStates.couponValidity &&
        Object.values(errorStates.couponValidity).some((value) => value !== null);

      return hasErrors || prev;
    });
  }, [errorStates.couponValidity]);

  return (
    <div>
      <Accordion
        open={isOpen}
        header={<div>Coupon validity</div>}
        body={<AccordionBody flow={flow} />}
      />
    </div>
  );
};

export default CouponValidityWidget;
