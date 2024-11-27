import { ArrowRightIcon, Box, Button, Radio, RadioGroup, Text } from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { LanguageOption } from 'apps/pos/src/app/utils/deviceDeployment';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface LanguageConfigurationProps {
  languageList: LanguageOption[];
  description: string;
  isUpdateModularLoading: boolean;
  deviceName: string;
  handleUpdateModularConfig: (payload: ModularPayload) => void;
}

const LanguageConfiguration = ({
  languageList,
  description,
  isUpdateModularLoading,
  deviceName,
  handleUpdateModularConfig,
}: LanguageConfigurationProps): JSX.Element => {
  const [language, setLanguage] = useState<string>('english');
  const { isMobile } = useScreen();
  const navigate = useNavigate();

  const handleProceed = () => {
    navigate(-1);
  };

  const handleConfirmLanguage = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Confirm Language',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section: 'Preferred Language',
        subSection: 'Preferred Language',
      },
    });
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_LANGUAGE_FIELD]: language,
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: handleProceed,
    };
    handleUpdateModularConfig(payload);
  };

  return (
    <Box padding={['spacing.5', 'spacing.6']}>
      <Text size="medium" weight="semibold" marginBottom="spacing.7">
        {description}
      </Text>

      <Box display="flex" marginBottom="spacing.6">
        <Text
          size="medium"
          weight="medium"
          color="surface.text.gray.subtle"
          marginRight="spacing.3"
        >
          Model Details
        </Text>
        <Text size="medium" weight="regular" color="surface.text.gray.subtle">
          {deviceName}
        </Text>
      </Box>

      <RadioGroup
        name={'language'}
        onChange={({ value }) => setLanguage(value)}
        defaultValue={language}
      >
        {languageList?.map((language) => (
          <Radio key={language.value} value={language.value}>
            {language.label}
          </Radio>
        ))}
      </RadioGroup>

      <Box
        display="flex"
        justifyContent="center"
        position={{ base: 'fixed', l: 'relative' }}
        bottom="0px"
        padding="spacing.4"
        backgroundColor={{
          base: 'surface.background.gray.intense',
          l: 'transparent',
        }}
        left="0px"
        right="0px"
        zIndex="1"
      >
        <Button
          icon={ArrowRightIcon}
          onClick={handleConfirmLanguage}
          size={isMobile ? 'medium' : 'large'}
          isFullWidth
          iconPosition="right"
          isLoading={isUpdateModularLoading}
        >
          Confirm Language
        </Button>
      </Box>
    </Box>
  );
};

export default LanguageConfiguration;
