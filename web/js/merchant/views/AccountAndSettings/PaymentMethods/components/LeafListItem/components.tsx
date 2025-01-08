import React from 'react';
import { Text, Heading } from '@razorpay/blade/components';

import { StyledLeafListItemHeader, StyledLeafListItemHeaderInfo } from './Styled';

type LeafListItemHeaderProps = {
  name: string;
  description: string;
  actionComponent?: JSX.Element;
};

const LeafListItemHeader = ({
  name,
  description,
  actionComponent,
}: LeafListItemHeaderProps): JSX.Element => {
  return (
    <StyledLeafListItemHeader>
      <StyledLeafListItemHeaderInfo>
        <Heading size="small">{name}</Heading>
        <Text size="small">{description}</Text>
      </StyledLeafListItemHeaderInfo>
      {!!actionComponent ? actionComponent : null}
    </StyledLeafListItemHeader>
  );
};

export { LeafListItemHeader };
