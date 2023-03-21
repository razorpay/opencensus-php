import React, { useState } from 'react';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import styled from 'styled-components';
import copyToClipboard from 'common/utils/copyToClipboard';

const ClickableContainer = styled(View)`
  cursor: pointer;
`;

const Accordion = ({ title, children, textToCopy, onOpen }) => {
  const [isOpen, setOpen] = useState(false);

  const onCopy = () => {
    copyToClipboard(JSON.stringify(textToCopy, undefined, 2));
  };

  const toggleAccordionOpen = () => {
    const toggleValue = !isOpen;
    setOpen(toggleValue);
    if (onOpen && toggleValue) onOpen();
  };

  return (
    <Size width="100%">
      <Space margin={[0, 0, 1, 0]}>
        <View>
          <Flex flexDirection="row" justifyContent="space-between">
            <View>
              <Flex flexDirection="row" alignItems="center" flex="1">
                <ClickableContainer onClick={toggleAccordionOpen}>
                  <Icon name={isOpen ? 'chevronDown' : 'chevronRight'} fill="white.960" />
                  <Text color="white.960">{title}</Text>
                </ClickableContainer>
              </Flex>
              <ClickableContainer
                onClick={onCopy}
                data-tip="Copied"
                data-event="active"
                data-testid="request-response-copy-button"
              >
                <Icon name="copy" fill="white.960" />
              </ClickableContainer>
            </View>
          </Flex>
          {isOpen ? <div data-testid="request-response-data">{children}</div> : null}
        </View>
      </Space>
    </Size>
  );
};

export default Accordion;
