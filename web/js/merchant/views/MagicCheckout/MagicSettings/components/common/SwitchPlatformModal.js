import Button from 'common/new-ui/Button';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const SwitchPlatformModal = ({ onCancel, onConfirm, platform }) => (
  <div className="gap--14 p--24 display-flex flex--column">
    <div className="switch-platform-modal--heading">
      Switch to {PLATFORMS.LABELS[platform.toUpperCase()]}
    </div>
    <div>Are you sure you want to switch platform?</div>
    <div>
      We can only allow one platform at a time. This will remove all your previous settings.
    </div>
    <div className="display-flex">
      <Button className="flex--1" onClick={onCancel}>
        No, don't switch
      </Button>
      <Button.Primary className="flex--1" onClick={onConfirm}>
        Yes, switch
      </Button.Primary>
    </div>
  </div>
);

export default SwitchPlatformModal;
