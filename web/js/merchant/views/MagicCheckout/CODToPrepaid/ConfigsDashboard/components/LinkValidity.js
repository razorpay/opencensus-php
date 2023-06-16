import React from 'react';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';

import {
  increaseValidity,
  decreaseValidity,
  customTimeValidators as validators,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/utils';

import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';

import {
  POPOVER_INFO_TEXT,
  VALIDITY_OPTIONS,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const CounterComponent = ({ unit, durationVal, setDurationVal }) => {
  return (
    <div className="counter-container">
      <i
        className="i i-arrow-up"
        onClick={() => increaseValidity(unit, durationVal, setDurationVal)}
        data-testid={`custom-${unit}-arrow-up`}
      />
      <i
        className="i i-arrow-down"
        onClick={() => decreaseValidity(unit, durationVal, setDurationVal)}
        data-testid={`custom-${unit}-arrow-down`}
      />
    </div>
  );
};

const LinkValidity = (props) => {
  const { validityType, setValidityType, durationVal, setDurationVal } = props;

  const editDurationVal = (e, type) => {
    const { value } = e.target;

    const parsedVal = value === '' ? value : parseInt(value, 10);
    const regex = new RegExp(/^\+?(0|[1-9]\d{0,2})?$/gm);

    if (!regex.test(parsedVal)) {
      return;
    }

    setDurationVal((prevVal) => ({ ...prevVal, [type]: parsedVal }));

    const errMsg = validators[type](parsedVal);
    if (errMsg !== '') {
      setDurationVal((prevState) => ({
        ...prevState,
        error: { ...prevState.error, [type]: errMsg },
      }));
    }
  };

  const editLinkValidity = (e) => {
    const { value } = e.target;
    if (value !== validityType) {
      setDurationVal({ hours: 0, mins: 0, error: { hours: null, mins: null } });
      setValidityType(value);
    }
  };

  return (
    <div className="config-box">
      <div className="configuration-label col-md-4">
        <label>
          Order conversion validity
          <sup className="magic-checkout-required"> *</sup>
          <i className="i i-info-outline intelligence-tooltip font-normal">
            <Popover theme="dark">
              <PopoverBody>
                <p>{POPOVER_INFO_TEXT.linkValidity}</p>
              </PopoverBody>
            </Popover>
          </i>
        </label>
      </div>
      <div className="configuration-value col-md-8 link-validity-container">
        <Input.Select
          id="linkValidity"
          name="linkValidity"
          options={VALIDITY_OPTIONS}
          value={validityType}
          onChange={editLinkValidity}
          className="link-validity-options"
          required
          requiredError="Required"
          data-testid="link-validity"
        />
        {validityType === 'custom' && (
          <div className="custom-time-container">
            <div className="custom-input-container">
              <Input
                addonAfter="Hours"
                className={`duration-value${durationVal.error.hours ? ' is-invalid' : ''}`}
                type="text"
                value={durationVal.hours}
                onWheel={onWheelPreventChange}
                onChange={(e) => editDurationVal(e, 'hours')}
                extraChildren={
                  <CounterComponent
                    unit="hours"
                    durationVal={durationVal}
                    setDurationVal={setDurationVal}
                  />
                }
                data-testid="custom-hours"
              />
              {durationVal.error.hours && (
                <p className="custom-time-error">{durationVal.error.hours}</p>
              )}
            </div>
            <div className="custom-input-container custom-minutes-container">
              <Input
                addonAfter="Minutes"
                className={`duration-value minutes-input${
                  durationVal.error.mins ? ' is-invalid' : ''
                }`}
                type="text"
                value={durationVal.mins}
                onWheel={onWheelPreventChange}
                onChange={(e) => editDurationVal(e, 'mins')}
                extraChildren={
                  <CounterComponent
                    unit="mins"
                    durationVal={durationVal}
                    setDurationVal={setDurationVal}
                  />
                }
                data-testid="custom-mins"
              />
              {durationVal.error.mins && (
                <p className="custom-time-error">{durationVal.error.mins}</p>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default LinkValidity;
