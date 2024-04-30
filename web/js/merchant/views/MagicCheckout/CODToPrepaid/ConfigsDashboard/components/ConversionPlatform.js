import React from 'react';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  POPOVER_INFO_TEXT,
  CONVERT_ON_OPTIONS,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';
import { PLATFORMS } from 'merchant/views/MagicCheckout/Settings/constants';

const ConversionPlatform = (props) => {
  const { convertOn, setConvertOn, platform } = props;

  const CONVERSION_OPTIONS =
    platform === PLATFORMS.WOOCOMMERCE ? [CONVERT_ON_OPTIONS[0]] : CONVERT_ON_OPTIONS;

  return (
    <div className="config-box">
      <div className="configuration-label col-md-4">
        <label>
          Convert order on
          <sup className="magic-checkout-required"> *</sup>
          <i className="i i-info-outline intelligence-tooltip font-normal">
            <Popover theme="dark">
              <PopoverBody>
                <p>{POPOVER_INFO_TEXT.conversionPlatform}</p>
              </PopoverBody>
            </Popover>
          </i>
        </label>
      </div>
      <div className="configuration-value col-md-8">
        <Input.Select
          id="conversionOn"
          name="conversionOn"
          options={CONVERSION_OPTIONS}
          value={convertOn}
          onChange={(e) => setConvertOn(e.target.value)}
          className="conversion-input"
          data-testid="conversion-platform"
        />
      </div>
    </div>
  );
};

export default ConversionPlatform;
