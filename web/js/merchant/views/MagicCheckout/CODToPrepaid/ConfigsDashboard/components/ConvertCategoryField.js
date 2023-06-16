import React from 'react';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';

import {
  POPOVER_INFO_TEXT,
  CONVERSION_LEVEL,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const ConvertCategoryField = (props) => {
  const { convertRiskCategory, setConvertRiskCategory } = props;

  return (
    <>
      <div className="config-box">
        <div className="configuration-label col-md-4">
          <label>
            Enable conversion for
            <sup className="magic-checkout-required"> *</sup>
            <i className="i i-info-outline intelligence-tooltip font-normal">
              <Popover theme="dark">
                <PopoverBody>
                  <p>{POPOVER_INFO_TEXT.riskCategory}</p>
                </PopoverBody>
              </Popover>
            </i>
          </label>
        </div>
        <div className="configuration-value col-md-8">
          <Input.Select
            id="convertRiskCategory"
            name="convertRiskCategory"
            options={CONVERSION_LEVEL}
            value={convertRiskCategory}
            onChange={(e) => setConvertRiskCategory(e.target.value)}
            className="riskCategoryOptions"
            data-testid="riskCategory"
          />
        </div>
      </div>
      <hr />
    </>
  );
};

export default ConvertCategoryField;
