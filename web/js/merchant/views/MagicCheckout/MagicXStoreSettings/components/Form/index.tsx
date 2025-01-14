import React, { useEffect, useReducer, useState } from 'react';
import {
  Box,
  Divider,
  Button,
  Switch,
  Text,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Input from 'common/new-ui/Input';
import { updateSopcMetafields } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import magicXReducer, { INITIAL_STATE } from 'merchant/reducers/magicCheckout/magicXStoreSettings';
import { showNotification } from 'merchant_common/reducers/notifications';

import { postMagicXStoreSettings } from './api';
import { transformFormtoServerData, transformServerDataToForm, validateForm } from './helpers';
import { ColorPickerWrapper } from './styled';

const Form = ({ settings, showNotification, updateSopcMetafields }) => {
  const [formState, dispatch] = useReducer(magicXReducer, INITIAL_STATE);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    const tranformedData = transformServerDataToForm(settings);
    dispatch({
      type: 'INITIALISE_DATA',
      payload: tranformedData,
    });
  }, []);

  const handleInputChange = (e) => {
    if (e.target) {
      dispatch({
        type: 'UPDATE_FIELD',
        payload: {
          [e.target.name]: e.target.value,
        },
      });
    }
  };

  const setFieldValue = (name: string, value: string) => {
    dispatch({
      type: 'UPDATE_FIELD',
      payload: {
        [name]: value,
      },
    });
  };

  const handleSwitchChange = (name: keyof typeof INITIAL_STATE) => {
    dispatch({
      type: 'UPDATE_FIELD',
      payload: {
        [name]: !formState[name],
      },
    });
  };

  const getShopifyDomain = () =>
    settings.shop_id?.includes('myshopify.com')
      ? settings.shop_id
      : `${settings.shop_id}.myshopify.com`;

  const handleSubmit = () => {
    const errorMessage = validateForm(formState);
    if (errorMessage) {
      showNotification({ type: 'error', message: errorMessage });

      return;
    }

    const transformedData = transformFormtoServerData(formState);

    setIsSaving(true);
    postMagicXStoreSettings(transformedData)
      .then(() => {
        updateSopcMetafields(transformedData);
        showNotification({ type: 'success', message: 'Settings saved successfully' });
        if (transformedData.status === 'live') {
          const themeAppExtensionDeepLink = `https://${getShopifyDomain()}/admin/themes/current/editor?context=apps&activateAppId=c13c688d-5c45-4054-b95f-1edd63faa705/magicx-script`;
          window.open(themeAppExtensionDeepLink, '_blank', 'noopener, noreferrer');
        }
      })
      .catch(() => {
        showNotification({ type: 'error', message: 'Failed to save settings' });
      })
      .finally(() => {
        setIsSaving(false);
      });
  };

  return (
    <Box>
      <Box
        display="flex"
        maxWidth="500px"
        paddingY="spacing.7"
        gap="spacing.7"
        flexDirection="column"
      >
        <Box width="100%" display="flex" paddingY="spacing.4">
          <Box width="50%">Enable Checkout360</Box>
          <Box>
            <Switch
              onChange={() => handleSwitchChange('status')}
              isChecked={formState.status}
              name="appEnabled"
              accessibilityLabel={`${formState.status ? 'Disable' : 'Enable'} Checkout360`}
            />
          </Box>
        </Box>
      </Box>
      <Divider />
      <Box
        display="flex"
        maxWidth="500px"
        paddingY="spacing.7"
        gap="spacing.7"
        flexDirection="column"
      >
        {/**
         * For Checkout360 flow , permlinks will be the only checkout type.
         * Commenting for future ref incase of change in requirements.
         */}
        {/* {isPlusPlan && (
          <Box width="100%" display="flex" alignItems="center">
            <Box width="50%">Checkout Type</Box>
            <Box width="50%">
              <Input.Select
                name="flowType"
                options={[
                  {
                    label: 'Checkout Prefill',
                    name: 'cart_permalinks',
                  },
                  {
                    label: 'Checkout Widgets',
                    name: 'checkout_ui_extensions',
                  },
                ]}
                value={formState.flowType}
                class="InputGroup--vTop"
                onChange={handleInputChange}
              />
            </Box>
          </Box>
        )} */}
        <Box width="100%" display="flex" alignItems="center">
          <Box width="50%">Email Field</Box>
          <Box width="50%">
            <Dropdown selectionType="single">
              <SelectInput
                label=""
                name="emailField"
                value={formState.emailField}
                onChange={({ name, values }) => {
                  setFieldValue(name as string, values[0]);
                }}
              />
              <DropdownOverlay>
                <ActionList>
                  <ActionListItem title="Hidden" value="hidden" />
                  <ActionListItem title="Mandatory" value="mandatory" />
                  <ActionListItem title="Optional" value="optional" />
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </Box>
        </Box>
        <Box width="100%" display="flex" alignItems="center">
          <Box width="50%">Theme Color</Box>
          <Box width="50%">
            <ColorPickerWrapper>
              <div className="color-picker">
                <input
                  name="themeColor"
                  type="color"
                  onChange={handleInputChange}
                  value={formState.themeColor}
                />
              </div>
              <Input
                class="Input--vTop"
                value={formState.themeColor}
                onChange={handleInputChange}
                name="themeColor"
                maxLength={7}
                pattern="^#[0-9A-Fa-f]{6}$"
              />
            </ColorPickerWrapper>
          </Box>
        </Box>
        <Box width="100%" display="flex" paddingY="spacing.4">
          <Box width="50%">Mandatory OTP</Box>
          <Box>
            <Switch
              onChange={() => handleSwitchChange('isLoginMandatory')}
              isChecked={formState.isLoginMandatory}
              name="isLoginMandatory"
              accessibilityLabel={`Make OTP ${
                formState.isLoginMandatory ? 'optional' : 'mandatory'
              }`}
            />
          </Box>
        </Box>
      </Box>
      <Divider />
      <Box
        display="flex"
        maxWidth="500px"
        paddingY="spacing.7"
        gap="spacing.7"
        flexDirection="column"
      >
        <Box width="100%" display="flex" alignItems="center">
          <Box width="50%">Cart Selector</Box>
          <Box width="50%">
            <Input
              class="Input--vTop"
              value={formState.cartSelector}
              onChange={handleInputChange}
              placeholder="Enter cart selector"
              name="cartSelector"
            />
          </Box>
        </Box>
        <Box width="100%" display="flex" alignItems="center">
          <Box width="50%">Product Selector</Box>
          <Box width="50%">
            <Input
              class="Input--vTop"
              value={formState.productSelector}
              onChange={handleInputChange}
              placeholder="Enter product selector"
              name="productSelector"
            />
          </Box>
        </Box>
      </Box>
      <Divider />
      <Box display="flex" alignItems="center" justifyContent="space-between">
        <Box maxWidth="75%">
          <Text>
            Upon clicking "Save Settings", you will be redirected to your Shopify admin and
            Checkout360 will be enabled on your live theme
          </Text>
        </Box>
        <Button
          onClick={handleSubmit}
          marginRight="none"
          isLoading={isSaving}
          marginTop="spacing.7"
        >
          Save Settings
        </Button>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
      updateSopcMetafields,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Form);
