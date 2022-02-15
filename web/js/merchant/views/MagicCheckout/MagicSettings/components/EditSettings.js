import { AsyncBtn } from 'common/new-ui/Button';
import { CONTENT, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const EditSettings = ({ platform, settings, onSave, children, valid }) => {
  return (
    <div className="flex flex--column h--100 w-100">
      <div className="flex--1 h--100 p--24">
        <div className="flex gap--12 align-center">
          {CONTENT[platform]?.LOGO && <img src={CONTENT[platform].LOGO} alt="platform-logo" />}
          <div className="font-bold font-heading text-uppercase">
            {CONTENT[platform]?.EDIT_LABEL}
          </div>
        </div>
        <div className="Form Form--tabular Form--magic-settings">{children}</div>
      </div>
      <div className="display-flex justify-end edit-settings-cta-container">
        <AsyncBtn.Primary
          type="button"
          isPending={settings.status === FETCH_STATUS.LOADING}
          onClick={onSave}
          disabled={!valid}
        >
          Apply Settings
        </AsyncBtn.Primary>
      </div>
    </div>
  );
};

export default EditSettings;
