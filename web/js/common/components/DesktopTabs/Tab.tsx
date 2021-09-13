import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Space from '@razorpay/blade-old/src/atoms/Space';

const StyledTabTitle = styled(View)`
  max-width: max-content;
  cursor: pointer;
`;

const StyledIcons = styled(View)`
  &:before {
    content: '';
    height: 11px;
    width: 1px;
    display: inline-block;
    background: rgba(22, 47, 86, 0.1);
    position: relative;
    bottom: 12px;
    left: 6.5px;
  }
  &:after {
    content: '';
    height: ${({ isDocumentTab }) => (isDocumentTab ? '' : '11px')};
    width: 1px;
    display: inline-block;
    background: rgba(22, 47, 86, 0.1);
    position: relative;
    top: 11px;
    right: 6.5px;
  }
`;

interface ITabProps {
  completed?: boolean;
  hasError?: boolean;
  isActive?: boolean;
  onClick?: () => void;
  title: string;
  tabId: string | number;
}

const Tab = ({
  completed = false,
  hasError = false,
  isActive = false,
  onClick,
  title,
  tabId,
}: ITabProps): React.ReactElement => {
  return (
    <Flex flex="none" justifyContent={isActive ? 'space-between' : ''}>
      <Space padding={[0.925, 1.75]}>
        <View>
          <Flex flex={1} alignItems="center">
            <StyledTabTitle onClick={onClick}>
              <Space margin={[0, 1.375, 0, 0]}>
                <StyledIcons isDocumentTab={tabId === 'documents'}>
                  {completed && !hasError ? (
                    <Icon name="checkedCircle" size="xsmall" fill="positive.960" />
                  ) : hasError ? (
                    <Icon name="alertCircle" size="xsmall" fill="negative.900" />
                  ) : (
                    <Icon
                      name="emptyCircle"
                      size="xsmall"
                      fill={isActive ? 'primary.800' : 'shade.980'}
                    />
                  )}
                </StyledIcons>
              </Space>
              <Text
                size="xsmall"
                color={isActive ? 'shade.980' : 'shade.970'}
                weight={isActive ? 'bold' : 'regular'}
              >
                {title}
              </Text>
            </StyledTabTitle>
          </Flex>

          {isActive ? (
            <Space margin={[0, 1.5, 0, 0]}>
              <View>
                <Icon name="chevronRight" size="xsmall" fill="primary.800" />
              </View>
            </Space>
          ) : null}
        </View>
      </Space>
    </Flex>
  );
};

export default Tab;
