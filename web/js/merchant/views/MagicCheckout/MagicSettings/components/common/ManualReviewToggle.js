import Popover, { PopoverBody } from 'common/ui/Popover';
import SwitchField from 'common/ui/Forms/SwitchField';

const ManualReviewToggle = ({ checked, switchMode }) => (
  <div className="filter-item link-account-instruction display-flex c-fee-configuration toggle-container">
    <div className="intelligence-label font-normal" htmlFor="manual-review">
      <label>
        Manually review COD orders
        <i className="i i-info-outline intelligence-tooltip font-normal">
          <Popover persistent={false} theme="dark">
            <PopoverBody>
              <p>
                COD option will be available for all your customers. COD Intelligence will run in
                the background and give you insights on potential RTOs. You can approve or cancel
                CODs, post order.
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

export default ManualReviewToggle;
