import React, { useState } from 'react';

import ActionContainer from 'merchant/views/RiskAndFraud/RiskAnalytics/components/ActionContainer';
import { BLOCK_CONTRIBUTORS } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import RequestBlacklist from './RequestBlacklist';
import { BlockRuleProps } from './types';

const BlockRule = ({ entity }: BlockRuleProps) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const { heading, description } = BLOCK_CONTRIBUTORS[entity];

  const onButtonClick = () => {
    setIsModalOpen(true);
  };

  const onDismiss = () => {
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
      <RequestBlacklist isOpen={isModalOpen} onDismiss={onDismiss} />
    </>
  );
};

export default BlockRule;
