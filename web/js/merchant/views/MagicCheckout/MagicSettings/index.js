import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { useCallback, useEffect, useState } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import { fetchMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import SwitchPlatformModal from 'merchant/views/MagicCheckout/MagicSettings/components/common/SwitchPlatformModal';
import {
  PLATFORMS_DROPDOWN,
  COMPONENTS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const MagicSettings = ({ settings, openModal, closeModal }) => {
  const [platform, setPlatform] = useState(PLATFORMS_DROPDOWN[0].name);
  const [Component, setComponent] = useState(null);

  useEffect(() => setPlatform(settings.platform), [settings.platform]);

  useEffect(() => {
    if (platform !== PLATFORMS_DROPDOWN[0].name) {
      setComponent(COMPONENTS[platform].platformComponent(settings.status));
    }
  }, [platform, setComponent, settings.status]);

  const onConfirm = useCallback(
    (value) => () => {
      setPlatform(value);
      closeModal();
    },
    [closeModal],
  );

  const onPlatformChange = useCallback(
    (e) => {
      if (e?.target?.value === PLATFORMS_DROPDOWN[0]?.name) {
        setPlatform(e?.target?.value);
        return;
      }
      if (e?.target?.value !== settings.platform && settings.has_saved_config) {
        openModal({
          size: 'medium',
          component: (
            <SwitchPlatformModal
              onConfirm={onConfirm(e?.target?.value)}
              onCancel={closeModal}
              platform={e?.target?.value}
            />
          ),
        });
        return;
      }
      setPlatform(e?.target?.value);
    },
    [settings, openModal, closeModal, onConfirm],
  );

  return (
    <div>
      <div className="selection-container platform-input-container display-flex align-center bg-settings font-normal">
        <label className="font-normal">Platform</label>
        <Input.Select
          name="platform"
          className="select-platform-dropdown"
          value={platform}
          options={PLATFORMS_DROPDOWN}
          onChange={onPlatformChange}
        />
      </div>
      {Component}
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      fetchSettings: fetchMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagicSettings);
