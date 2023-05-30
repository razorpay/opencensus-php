import SwitchField from 'common/ui/Forms/SwitchField';

const CodSettingsToggle = ({ checked, switchMode }) => {
  return (
    <div className="filter-item link-account-instruction display-flex c-fee-configuration toggle-container">
      <div className="cod-settings-label font-normal" for="cod-cod-settings">
        <label>COD as payment option</label>
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

export default CodSettingsToggle;
