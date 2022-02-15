import Popover, { PopoverBody } from 'common/ui/Popover';

const SettingsInputLabel = ({ label, children }) => (
  <label>
    {label}
    <i className="i i-info-outline">
      <Popover persistent={false} theme="dark">
        <PopoverBody>
          <p>{children}</p>
        </PopoverBody>
      </Popover>
    </i>
  </label>
);

export { SettingsInputLabel };
