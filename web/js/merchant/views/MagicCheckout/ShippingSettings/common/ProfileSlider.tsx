import React, { useState } from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import ShippingMethodsTable from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/ShippingMethodsTable';
import { connect } from 'react-redux';
import styled from 'styled-components';

import Slider from 'common/ui/Slider';
import {
  Separator,
  SettingsWrapper,
  SliderWrapper,
  SliderItem,
} from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import { openSlider } from 'merchant_common/reducers/slider';

const ProfileName = styled.p`
  text-decoration-line: underline;
  color: #0b70e7;
  cursor: pointer;
`;

const ProfileSlider = ({ profile, openSlider }) => {
  const [isOpen, setIsOpen] = useState(false);
  const handleClick = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsOpen(true);
    openSlider();
  };

  return (
    <Box testID="profile-slider">
      <ProfileName onClick={handleClick}>{profile.name}</ProfileName>
      {isOpen ? (
        <Slider>
          <SliderWrapper>
            <Heading size="large">{profile.name}</Heading>
            <Separator />
            {profile?.zones?.length > 0 ? (
              profile.zones?.map((zone, index) => {
                return (
                  <SliderItem key={index}>
                    <SettingsWrapper>
                      <Text size="large" weight="bold">
                        {zone.name}
                      </Text>
                      {zone.shipping_methods?.length > 0 ? (
                        <ShippingMethodsTable shipping_methods={zone.shipping_methods} />
                      ) : (
                        <Text>No methods defined</Text>
                      )}
                    </SettingsWrapper>
                  </SliderItem>
                );
              })
            ) : (
              <Text>No zones added for this category</Text>
            )}
          </SliderWrapper>
        </Slider>
      ) : null}
    </Box>
  );
};

export default connect(null, { openSlider })(ProfileSlider);
