import React from 'react';
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
        <h3>{name}</h3>
        <p>{description}</p>
      </StyledLeafListItemHeaderInfo>
      {!!actionComponent ? actionComponent : null}
    </StyledLeafListItemHeader>
  );
};

export { LeafListItemHeader };
