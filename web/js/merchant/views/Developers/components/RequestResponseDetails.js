import React, { useState } from 'react';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';

const Accordion = ({ title, children, textToCopy, onOpen }) => {
  const [isOpen, setOpen] = useState(false);

  const onCopy = () => {
    const $body = document.getElementsByTagName('body')[0];
    const $tempInput = document.createElement('INPUT');
    $body.appendChild($tempInput);
    $tempInput.setAttribute('value', textToCopy);
    $tempInput.select();
    document.execCommand('copy');
    $body.removeChild($tempInput);
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
                <View onClick={toggleAccordionOpen} style={{ cursor: 'pointer' }}>
                  <Icon name={isOpen ? 'chevronDown' : 'chevronRight'} fill="white.960" />
                  <Text color="white.960">{title}</Text>
                </View>
              </Flex>
              <View
                onClick={onCopy}
                style={{ cursor: 'pointer' }}
                data-tip="Copied"
                data-event="active"
              >
                <Icon name="copy" fill="white.960" />
              </View>
            </View>
          </Flex>
          {isOpen ? children : null}
        </View>
      </Space>
    </Size>
  );
};

export default Accordion;
