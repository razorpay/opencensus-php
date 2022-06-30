import { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { AsyncBtn } from 'common/new-ui/Button';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { analyticsTrack } from 'common/utils/analytics';

const NativePlatform = ({ status, merchantId, updateSettings }) => {
  const onSave = useCallback(() => {
    analyticsTrack({
      objectName: '1ccclickednextonplatformsettings',
      actionName: 'behav',
      screen: 'platform settings l0',
      properties: {
        platform: PLATFORMS.VALUES.NATIVE,
        merchant_id: merchantId,
      },
    });
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

const mapStateToProps = (state) => ({
  merchantId: state.config?.config?.id,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(NativePlatform);
