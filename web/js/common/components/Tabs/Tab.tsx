import React, { useRef, useEffect } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';

const StyledTabTitle = styled(View)`
  min-width: max-content;
  cursor: pointer;
`;

const Highlight = styled(View)`
  background-color: ${({ theme }) => theme.colors.primary[800]};
`;

interface TabProps {
  completed?: boolean;
  hasError?: boolean;
  isActive?: boolean;
  onClick?: () => void;
  title: string;
  // eslint-disable-next-line react/no-unused-prop-types
  tabId: string | number;
  // eslint-disable-next-line react/no-unused-prop-types
  children: React.ReactElement | React.ReactElement[];
}

const Tab = ({
  completed = false,
  hasError = false,
  isActive = false,
  onClick,
  title,
}: TabProps): React.ReactElement => {
  const activeTabRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (activeTabRef.current) {
      activeTabRef.current.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center',
      });
    }
  }, [isActive]);

  return (
    <Flex flexDirection="column" flex="none">
      <Space padding={[0, 1.75]}>
        <View>
          <Flex flex={1} alignItems="center">
            <StyledTabTitle ref={isActive ? activeTabRef : null} onClick={onClick}>
              {completed && !hasError ? (
                <Space margin={[0, 0.5, 0, 0]}>
                  <View>
                    <Icon name="success" size="xsmall" fill="positive.960" />
                  </View>
                </Space>
              ) : hasError ? (
                <Space margin={[0, 0.5, 0, 0]}>
                  <View>
                    <Icon name="alertCircle" size="xsmall" fill="negative.900" />
                  </View>
                </Space>
              ) : null}
              <Text size="xsmall" color={isActive ? 'primary.800' : 'shade.960'}>
                {title}
              </Text>
            </StyledTabTitle>
          </Flex>

          {isActive ? (
            <Size height={0.125}>
              <Highlight />
            </Size>
          ) : null}
        </View>
      </Space>
    </Flex>
  );
};

export default Tab;
