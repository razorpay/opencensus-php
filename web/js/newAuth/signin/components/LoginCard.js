import React, { useEffect } from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import { CustomLinkButton, Image } from '../styles';

const LoginCard = ({ cardData, cardOrder }) => {
  let hoverEventFired = false;
  const { type, title, imgSrc, imgAlt, desc, ctaURL, ctaText, id } = cardData;

  const handleHoverEvent = () => {
    if (!hoverEventFired) {
      if (window.rzpQ.push) {
        window.rzpQ.push(
          window.rzpQ.onbr().success('login.non_login_card.hover', {
            CardID: id,
          }),
        );
      }
      hoverEventFired = true;
    }
  };

  const handleCTAClick = () => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.onbr().initiated('login.non_login_actions', {
          action: `click Promotion ${cardOrder} CTA`,
          promotion_title: title,
          CardID: id,
        }),
      );
    }
  };

  useEffect(() => {
    if (window.rzpQ.push) {
      window.rzpQ.push(
        window.rzpQ.onbr().success('login.non_login_card.shown', {
          CardID: id,
        }),
      );
    }
  }, []);

  const Header =
    type === 'with-image' ? (
      <Space margin={[0, 0, 1, 0]}>
        <Size maxWidth="400px">
          <Image src={imgSrc} alt={imgAlt} />
        </Size>
      </Space>
    ) : (
      <Space margin={[3.5, 0, 0, 0]}>
        <Text size="large" color="shade.700" weight="bold" align="left">
          {title}
        </Text>
      </Space>
    );

  return (
    <View onMouseEnter={handleHoverEvent}>
      {Header}
      <Space margin={[1, 0, 1, 0]}>
        <Text size="medium" color="shade.700">
          {desc}
        </Text>
      </Space>
      <Space margin={[1, 0, 3, 0]} padding={[0]}>
        <CustomLinkButton as="a" href={ctaURL} target="_blank" onClick={handleCTAClick}>
          {ctaText}
          <span>→</span>
        </CustomLinkButton>
      </Space>
    </View>
  );
};

export default LoginCard;
