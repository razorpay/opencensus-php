import { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { AsyncBtn } from 'common/new-ui/Button';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const NativePlatform = ({ status, updateSettings }) => {
  const onSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.NATIVE,
      list_promotions: ``,
      apply_promotion: ``,
      shipping_info: ``,
    });
  }, [updateSettings]);

  return (
    <AsyncBtn.Primary
      type="button"
      isPending={status === FETCH_STATUS.LOADING}
      onClick={onSave}
      className="settings-cta"
    >
      Next
    </AsyncBtn.Primary>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(NativePlatform);
