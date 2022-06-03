import { CONTENT } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const SettingsCard = ({ children, platform, onEdit }) => (
  <div className="platform-settings-card">
    <div className="display-flex justify-space-between align-center platform-settings-card--header">
      <div className="inline-flex align-center">
        {CONTENT[platform]?.LOGO && <img src={CONTENT[platform].LOGO} alt="platform-logo" />}
        <span className="platform-heading">{CONTENT[platform]?.EDIT_LABEL}</span>
      </div>
      <i className="i i-edit" onClick={onEdit} />
    </div>
    <div className="display-flex flex--column platform-settings-card--body">{children}</div>
  </div>
);

SettingsCard.Item = ({ label, value }) => (
  <div className="flex flex--column gap--4">
    <div className="setting-label">{label}</div>
    <div className="setting-value font-bold">{value}</div>
  </div>
);

export default SettingsCard;
