import React from 'react';
import { Text, Box, Switch, AlertTriangleIcon, Link } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import SwitchField from 'common/ui/Forms/SwitchField';

import {
  SETUP_MAGICX_V1_ROUTE,
  SETUP_MAGICX_V2_ROUTE,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';
import {
  MAGIC_DASHBOARD_REVAMP_EXPERIMENT,
  MAGICX_PUBLICAPP_COD_EXPERIMENT,
} from 'merchant/views/MagicCheckout/constants';
import { checkMagicConfigurationFlow } from 'merchant/views/MagicCheckout/utils/Configuration';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

/*
 * TODOs:
 * 1. refactor this component to make it reusable in accordance to new design
 * 2. Move this component to `MagicCheckout/Settings`
 */
const CodIntelligenceToggle = ({ checked, switchMode, sopcMetafields, rcodEnabled }) => {
  const navigate = useNavigate();
  const isMagicXCodEnabled = useMagicExperiment(MAGICX_PUBLICAPP_COD_EXPERIMENT);

  /**
   * Path to redirect user to setup magicX checkout based on dashboard revamp EXP
   */
  const magicXSetupPath = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
    ? SETUP_MAGICX_V2_ROUTE
    : SETUP_MAGICX_V1_ROUTE;

  return rcodEnabled && isMagicXCodEnabled ? (
    <Box
      borderWidth="thinner"
      borderColor="surface.border.gray.subtle"
      padding="spacing.5"
      maxWidth="40%"
      borderRadius="medium"
    >
      <Box display="flex" justifyContent="space-between" alignItems="center">
        <Text marginRight="spacing.10" marginLeft="spacing.3" weight="semibold">
          Enable RTO Intelligence
        </Text>
        <Switch
          accessibilityLabel="magicx cod intelligence"
          marginX="spacing.3"
          isChecked={checked}
          onChange={switchMode}
          name="codIntelligence"
        />
      </Box>
      {checked && !(sopcMetafields?.status === 'live') && (
        <Box display="flex" marginBottom="-15px" alignItems="center">
          <AlertTriangleIcon
            color="feedback.icon.notice.intense"
            marginX="spacing.3"
            size="small"
          />
          <Text variant="body" size="small">
            <Link
              onClick={() => {
                navigate(
                  //Support to render on Dashboard Full Page View mode
                  checkMagicConfigurationFlow()
                    ? magicXSetupPath?.replace('/magic/', '/configuration/magic/')
                    : magicXSetupPath,
                );
              }}
              marginRight="spacing.2"
              size="small"
            >
              Activate
            </Link>
            Checkout360 for this to work.
          </Text>
        </Box>
      )}
    </Box>
  ) : (
    <div className="filter-item link-account-instruction display-flex c-fee-configuration toggle-container">
      <div className="intelligence-label font-normal" for="cod-intelligence">
        <label>
          COD Intelligence
          <i className="i i-info-outline intelligence-tooltip font-normal">
            <Popover persistent={false} theme="dark">
              <PopoverBody>
                <p>
                  By enabling this you allow Magic Checkout to decide which customer sees the COD
                  option based on past buying history.
                </p>
              </PopoverBody>
            </Popover>
          </i>
        </label>
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

export default CodIntelligenceToggle;
