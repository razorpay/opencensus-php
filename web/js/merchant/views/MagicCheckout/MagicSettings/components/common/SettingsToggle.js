import SwitchField from 'common/ui/Forms/SwitchField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import isEmpty from 'lodash/isEmpty';

const SettingsToggle = ({ setting, onToggle }) =>
  !isEmpty(setting) ? (
    <div className="display-flex checkout-settings-toggle">
      {setting.label && (
        <div className="setting-label">
          {setting.label}
          {setting.description && (
            <i className="i i-info-outline">
              <Popover theme="dark">
                <PopoverBody>
                  <div>{setting.description}</div>
                </PopoverBody>
              </Popover>
            </i>
          )}
        </div>
      )}
      <div className="display-flex setting-toggle">
        <SwitchField
          onChange={(checked, postActionCB) => onToggle(checked, setting.label, postActionCB)}
          checked={setting.value}
          defaultChecked={setting.value}
          type="prime"
          name="settings-toggle"
        />
        {setting.value ? (
          <b className="text-primary toggle-status">Enabled</b>
        ) : (
          <b className="text-faded toggle-status">Disabled</b>
        )}
      </div>
    </div>
  ) : null;

export default SettingsToggle;
