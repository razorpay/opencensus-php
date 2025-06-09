import React, { useCallback, useEffect, useState } from 'react';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';
import { connect } from 'react-redux';

import { isUrlLenient } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';

import Popover, { PopoverBody } from 'common/ui/Popover';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { FETCH_STATUS, PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { updateDefaultViewInStorage } from 'merchant/views/MagicCheckout/utils/storeSettings';

const isUrlValid = (value: string) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return '';
};

const SettingsForm = ({ settings, updateSettings, merchantId }) => {
  const [isFormValid, setIsFormValid] = useState(false);
  const [domain, setDomain] = useState('');

  const onChange = useCallback(
    (setStore) => (e) => {
      setStore(e.target.value);
    },
    [],
  );

  useEffect(() => {
    setIsFormValid(isUrlLenient(domain));
  }, [domain]);

  useEffect(() => {
    if (settings.platform === PLATFORMS.VALUES.MAGENTO && settings.domain_url) {
      setDomain(settings.domain_url as string);
    }
  }, [settings.platform, settings.domain_url]);

  const handleSave = useCallback(() => {
    updateSettings({
      platform: PLATFORMS.VALUES.MAGENTO,
      domain_url: domain,
    });
    updateDefaultViewInStorage(merchantId, true);
    analyticsTrack({
      objectName: '1ccclickednextonplatformsettings',
      actionName: 'behav',
      screen: 'platform settings l0',
      properties: {
        platform: PLATFORMS.VALUES.MAGENTO,
        domain_hyperlink: domain,
        merchant_id: merchantId,
      },
    });
  }, [updateSettings, domain, merchantId]);

  return (
    <div>
      <div className="bg-settings platform-input-container magento-container">
        <Input
          required={true}
          validator={isUrlValid}
          value={domain}
          onChange={onChange(setDomain)}
          label={() => (
            <label>
              Domain hyperlink{' '}
              <i className="i i-info-outline font-normal margin-l--4">
                <Popover persistent={false} theme="dark">
                  <PopoverBody>
                    <p>The URL of your Magento store, e.g. https://myecomstore.com</p>
                  </PopoverBody>
                </Popover>
              </i>
            </label>
          )}
          name="domain"
          placeholder="Enter domain hyperlink"
          id="domain"
          className="Magic-Settings--Input"
        />
      </div>
      <AsyncBtn.Primary
        type="button"
        isPending={settings.status === FETCH_STATUS.LOADING}
        onClick={handleSave}
        disabled={!isFormValid}
        className="settings-cta"
      >
        Next
      </AsyncBtn.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators({ updateSettings: updateMagicSettings }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(SettingsForm);
