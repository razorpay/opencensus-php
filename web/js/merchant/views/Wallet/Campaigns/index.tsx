import {
  Box,
  Button,
  Divider,
  Heading,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  PlusIcon,
  Radio,
  RadioGroup,
  TextInput,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { CampaignTypeEnum, CampaignType } from './types';
import CampaignsList from './CampaignsList';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const Campaigns = () => {
  const [showCampaignOptionModal, setShowCampaignOptionModal] = useState(false);
  const [campaignType, setCampaignType] = useState<CampaignType>(CampaignTypeEnum.TRIGGER);
  const [campaignName, setCampaignName] = useState('');
  const splitz = useSplitzService();

  const navigate = useNavigate();

  const handleCampaignCreation = () => {
    setShowCampaignOptionModal(false);
    navigate(
      `/wallet/campaigns/new?campaignName=${encodeURIComponent(
        campaignName,
      )}&campaignType=${campaignType}`,
    );
  };

  const handleNewCampaignCreation = () => {
    setShowCampaignOptionModal(true);
  };

  useEffect(() => {
    if (!isExperimentEnabled(splitz?.abExperiments?.wallet_campaigns)) {
      navigate('/wallet', { replace: true });
    }
  }, []);

  return (
    <div className="content-wrapper">
      <Box display="flex" flexDirection="column">
        <Box display="flex" justifyContent="space-between" marginBottom="spacing.7">
          <Heading size="small" weight="semibold">
            Active Campaigns
          </Heading>
          <Button icon={PlusIcon} onClick={handleNewCampaignCreation}>
            New Campaign
          </Button>
        </Box>
        <Box display="flex" flex={1} justifyContent="center">
          <CampaignsList onNewCampaignCreate={handleNewCampaignCreation} />
        </Box>
        <Modal
          isOpen={showCampaignOptionModal}
          onDismiss={() => {
            setShowCampaignOptionModal(false);
          }}
          size="small"
        >
          <ModalHeader title="Create New Campaign" />
          <ModalBody>
            <TextInput
              label="Name"
              placeholder="Eg. Festive Cashback"
              value={campaignName}
              onChange={({ value }) => setCampaignName(value ?? '')}
              isRequired
            />
            <Divider dividerStyle="dashed" marginY="spacing.6" />
            <RadioGroup
              label="Type"
              value={campaignType}
              isRequired
              onChange={({ value }) => setCampaignType(value as CampaignType)}
            >
              <Radio
                value={CampaignTypeEnum.TRIGGER}
                helpText="Credit points when an event is triggered"
              >
                Trigger Based Campaign
              </Radio>
              <Radio value={CampaignTypeEnum.ONE_TIME} helpText="Coming soon!" isDisabled>
                One Time Campaign
              </Radio>
            </RadioGroup>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" justifyContent="flex-end">
              <Button
                type="submit"
                onClick={handleCampaignCreation}
                isDisabled={!campaignName || !campaignType}
              >
                Create Campaign
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      </Box>
    </div>
  );
};

export default Campaigns;
