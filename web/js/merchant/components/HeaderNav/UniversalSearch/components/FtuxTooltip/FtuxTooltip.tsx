import { Text } from '@razorpay/blade/components';
import { FadeTransition } from 'common/components/Transition';
import { trackSearchBarInfo } from 'merchant/components/HeaderNav/UniversalSearch/utils';
import {
  hideFtux,
  isVisible,
} from 'merchant/components/HeaderNav/UniversalSearch/utils/ftuxVisibility';
import React, { useEffect, useState } from 'react';
import { FtuxAction, StyledFtuxContainer } from './styled';

const FtuxTooltip = ({
  setIsFtuxVisible,
}: {
  setIsFtuxVisible: React.Dispatch<React.SetStateAction<boolean>>;
}): JSX.Element | null => {
  const [isShow, setIsShow] = useState<boolean>(false);

  const getUpdatedVisibility = () => {
    const isShowFTUX = isVisible();
    setIsShow(isShowFTUX);
  };

  const handleClick = () => {
    hideFtux({ onGotIt: true });
    setIsFtuxVisible(false);
    getUpdatedVisibility();
    trackSearchBarInfo();
  };

  useEffect(() => {
    getUpdatedVisibility();
    setIsFtuxVisible(true);
  }, []);

  if (!isShow) {
    return null;
  }

  return (
    <FadeTransition duration={400} in={isShow} appear>
      <StyledFtuxContainer>
        <Text weight="bold" type="subtle" contrast="high">
          Introducing Search
        </Text>
        <Text type="subtle" contrast="high">
          You can search for payment products, Account & Settings, and more
        </Text>
        <FtuxAction onClick={handleClick}>
          <Text weight="bold" type="subtle" contrast="high" data-testid="search-ftux-gotit">
            GOT IT
          </Text>
        </FtuxAction>
      </StyledFtuxContainer>
    </FadeTransition>
  );
};

export default FtuxTooltip;
