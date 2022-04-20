import Popover, { PopoverBody } from 'common/ui/Popover';
import SwitchField from 'common/ui/Forms/SwitchField';
import { classList } from 'common/utils/rzp-utils';

const CodIntelligenceToggle = ({ platform, checked, switchMode }) => {
  return (
    <div className="filter-item link-account-instruction display-flex c-fee-configuration">
      <div
        className={
          platform === 'shopify' ? 'Input-label' : classList('intelligence-label', 'font-bold')
        }
        for="cod-intelligence"
      >
        <label>
          COD Intelligence
          <i className="i i-info-outline">
            <Popover persistent={false} theme="dark">
              <PopoverBody>
                <p>
                  By enabling this you allow Magic Checkout to decide which customer sees the COD
                  option based on past buying history.
                </p>
              </PopoverBody>
            </Popover>
          </i>
        </label>
      </div>
      <div className="width-full">
        <div className="display-flex justify-space-between slabs-container">
          <span className="toggler-btn">
            <SwitchField checked={checked} type="prime" onChange={switchMode} />
            {checked ? (
              <b className="text-primary toggle-status">Enabled</b>
            ) : (
              <b className="text-faded toggle-status">Disabled</b>
            )}
          </span>
        </div>
      </div>
    </div>
  );
};

export default CodIntelligenceToggle;
