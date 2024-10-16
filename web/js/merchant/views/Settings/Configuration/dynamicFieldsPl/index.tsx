import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import {
  ActionList,
  ActionListItem,
  Box,
  Checkbox,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  Text,
  TextInput,
  Button,
} from '@razorpay/blade/components';

import TextHighlighter from 'common/ui/TextHighlighter';
import { isInteger } from 'common/utils/validators';
import { deepCopy } from 'common/utils/immutable';

import { showNotification } from 'merchant_common/reducers/notifications';

import { fetchPaymentLinkCustomFields } from 'merchant/reducers/paymentlinks/details';
import { merchantFetch } from 'merchant/utils/ajax';

import { DYNAMIC_FIELDS_PL } from 'merchant/views/Settings/Configuration/deeplink-constants';

import { createPayload, formattedSuffix, isSaveNotAllowed } from './utils';
import { INITIAL_CONFIG } from './constants';
import { Config } from './types';

function updatePaymentLinkCustomFields(payload) {
  return merchantFetch({
    url: 'payment_links/configuration/custom_fields',
    method: 'post',
    data: payload,
  });
}

function DynamicFieldsPl({ showNotification }): JSX.Element {
  const [configurations, setConfigurations] = useState<Config[]>([INITIAL_CONFIG]);
  const [isLoading, setIsLoading] = useState(true);

  const initialConfigurations = useRef<Config[]>([]);

  const getConfigurations = async () => {
    try {
      const res = await fetchPaymentLinkCustomFields();
      const fields = res?.data?.configurations || [];

      if (fields.length > 0) {
        const configs = fields.map(({ configuration }) => ({
          ...configuration,
          suffix: formattedSuffix(configuration.masking_length),
        }));

        setConfigurations(configs);

        initialConfigurations.current = configs;
      }
    } catch (error: any) {
      showNotification({ type: 'error', message: error.errors.join(' ') });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    getConfigurations();
  }, []);

  const handleFormChange = (params, index: number) => {
    const { name, value } = params;

    const configs = deepCopy(configurations);
    const config = configs[index];

    config[name] = value;

    if (name === 'masking_length') {
      config.suffix = formattedSuffix(value);
    }

    setConfigurations(configs);
  };

  const handleIsMandatory = (params, index: number) => {
    const { event, isChecked } = params;

    handleFormChange({ name: event.target.name, value: isChecked }, index);
  };

  const handleType = (params, index: number) => {
    handleFormChange({ ...params, value: params.values[0] }, index);
  };

  const handleMaskingLength = (params, index: number) => {
    const { value } = params;

    if (isInteger(value) || value === '') {
      handleFormChange(params, index);
    }
  };

  const handleSave = async () => {
    if (isSaveNotAllowed(configurations)) {
      return;
    }

    const payload = createPayload(initialConfigurations.current, configurations);

    try {
      setIsLoading(true);

      const res = await updatePaymentLinkCustomFields(payload);

      if (res.success) {
        showNotification({ type: 'success', message: 'Configurations updated successfully' });
      }
    } catch (error: any) {
      showNotification({ type: 'error', message: error.errors.join(' ') });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <span className="title">
          <TextHighlighter hashedWith={DYNAMIC_FIELDS_PL}>
            Rename the Fields on Payment Links
          </TextHighlighter>
        </span>
      </div>
      <div className="panel-body">
        <form className="form-horizontal">
          {configurations.map(({ label, is_mandatory, masking_length, type, suffix }, index) => {
            return (
              <>
                <Box display="flex" gap="spacing.6" flexDirection={{ base: 'column', m: 'row' }}>
                  <Box flex="60%">
                    <TextInput
                      label="Field Label"
                      placeholder="Enter field label"
                      name="label"
                      labelPosition="left"
                      value={label}
                      onChange={(params) => handleFormChange(params, index)}
                      necessityIndicator="required"
                      isDisabled={isLoading}
                    />
                  </Box>

                  <Box flex="40%" display="flex" alignItems="center">
                    <Checkbox
                      name="is_mandatory"
                      isChecked={is_mandatory}
                      onChange={(params) => handleIsMandatory(params, index)}
                      isDisabled={isLoading}
                    >
                      <Text
                        weight="semibold"
                        marginLeft="spacing.3"
                        color="surface.text.gray.muted"
                      >
                        Is Mandatory
                      </Text>
                    </Checkbox>
                  </Box>
                </Box>
                <Box marginTop="spacing.5">
                  <TextInput
                    label="Characters"
                    helpText="Enter the number of character to be masked and on the right check how it looks. Enter 0 for no character to be masked."
                    placeholder="Enter the number"
                    name="masking_length"
                    labelPosition="left"
                    value={masking_length}
                    type="number"
                    suffix={suffix}
                    onChange={(params) => handleMaskingLength(params, index)}
                    necessityIndicator="required"
                    isDisabled={isLoading}
                  />
                </Box>
                <Box marginTop="spacing.5">
                  <Dropdown>
                    <SelectInput
                      label="Field type"
                      placeholder="Select field type"
                      name="type"
                      labelPosition="left"
                      value={type}
                      onChange={(params) => handleType(params, index)}
                      necessityIndicator="required"
                      isDisabled={isLoading}
                    />
                    <DropdownOverlay>
                      <ActionList>
                        <ActionListItem title="Integer" value="integer" />
                        <ActionListItem title="String" value="string" />
                      </ActionList>
                    </DropdownOverlay>
                  </Dropdown>
                </Box>
                <Box display="flex" justifyContent="flex-end" marginTop="spacing.5">
                  <Button
                    size="medium"
                    isLoading={isLoading}
                    isDisabled={isLoading || isSaveNotAllowed(configurations)}
                    onClick={handleSave}
                    testID="save-config-cta"
                  >
                    Save Changes
                  </Button>
                </Box>
              </>
            );
          })}
        </form>
      </div>
    </div>
  );
}

export default connect(null, { showNotification })(DynamicFieldsPl);
