import Time from 'common/ui/Time';
// import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';

const TIME_FORMAT = 'DD MMM';

const Info = ({
  plan,
  trialDate,
  billingDate,
  isTrialVisible = true,
  isPopOverVisible = false,
}) => {
  return (
    <div className="info-wrapper-mopl">
      <div className="flex-space-between">
        <div id="missed-order-plan-title">{titleCase(plan?.name)}</div>
      </div>
      {isTrialVisible && (
        <div className="flex-space-between">
          <div className="middle-align">
            <div>Trial ends on</div>
            {isPopOverVisible && (
              <>
                <i className="i i-help-outline left-space" />
                <Popover
                  align="bottom"
                  theme="dark"
                  parentQuerySelector=".manage-settings-container"
                >
                  {' '}
                  <PopoverBody>
                    You will be able to purchase Remarketer after your trial ends
                  </PopoverBody>
                </Popover>
              </>
            )}
          </div>
          <div>
            <Time value={trialDate} format={TIME_FORMAT} />
          </div>
        </div>
      )}
      <div className="flex-space-between">
        <div>Next bill on</div>
        <div>
          <Time value={billingDate} format={TIME_FORMAT} />
        </div>
      </div>
    </div>
  );
};

export default Info;
