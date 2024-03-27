import React, { useState } from 'react';

import ActionContainer from 'merchant/views/RiskAndFraud/RiskAnalytics/components/ActionContainer';
import { BLOCK_CONTRIBUTORS } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import RequestBlacklist from './RequestBlacklist';
import { BlockRuleProps } from './types';
import { trackEvent } from '../../common/trackEvents';

const BlockRule = ({ entity }: BlockRuleProps) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const { heading, description } = BLOCK_CONTRIBUTORS[entity];

  const onButtonClick = () => {
    trackEvent({
      objectName: 'Request blacklist - Open',
      properties: { section: entity },
    });
    setIsModalOpen(true);
  };

  const onDismiss = () => {
    trackEvent({
      objectName: 'Request blacklist - Close',
      properties: { section: entity },
    });
    setIsModalOpen(false);
  };

  return (
    <>
      <ActionContainer
        heading={heading}
        description={description}
        onButtonClick={onButtonClick}
        buttonText="Request blacklist"
      />
      <RequestBlacklist entity={entity} isOpen={isModalOpen} onDismiss={onDismiss} />
    </>
  );
};

export default BlockRule;
