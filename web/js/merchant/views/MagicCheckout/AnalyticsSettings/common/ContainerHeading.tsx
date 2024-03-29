import React from 'react';
import { Button, PlusCircleIcon, Text } from '@razorpay/blade/components';
import styled from 'styled-components';

import { ContainerHeadingPropType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

const HeadingContent = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;

  button:disabled {
    cursor: not-allowed;
  }
`;

const ContainerHeading = (props: ContainerHeadingPropType): JSX.Element => {
  const { header, onAddAccount, isCtaDisabled } = props;

  return (
    <HeadingContent>
      <Text weight="semibold" color="surface.text.gray.subtle" size="large">
        {header}
      </Text>
      <Button
        type="button"
        size="medium"
        icon={PlusCircleIcon}
        iconPosition="left"
        onClick={onAddAccount}
        isDisabled={isCtaDisabled}
      >
        Add account
      </Button>
    </HeadingContent>
  );
};

export default ContainerHeading;
