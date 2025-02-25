import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Button from '../../shared/Button';
import DesktopOnlyView from '../../shared/responsiveView/DesktopOnlyView';
import MobileOnlyView from '../../shared/responsiveView/MobileOnlyView';

const OverlayContainer = styled(View)`
  position: fixed;
  top: 0;
  bottom: 0;
  width: 100%;
  left: 0;
  right: 0;
  background: ${(props) => props.theme.colors.shade[950]};
  z-index: 10;
`;

const ContentContainer = styled(View)`
  background: ${(props) => props.theme.colors.background[100]};
  border-radius: 4px;
  margin: auto;
  @media (max-width: 768px) {
    max-width: 312px;
  }
`;

const ConfirmationModal = ({ couponCode, closeModal, confirmModal }) => {
  return (
    <Flex justifyContent="space-between" flexDirection="column">
      <OverlayContainer>
        <Flex justifyContent="space-between" flexDirection="column">
          <Space padding={[3.5, 5]}>
            <Size maxWidth="464px">
              <ContentContainer>
                <View>
                  <Heading weight="bold" size="xlarge">
                    Remove Coupon
                  </Heading>
                  <Space padding={[0.5, 0]}>
                    <Text size="large" color="shade.980">
                      Are you sure you want to remove the coupon code {couponCode}?
                    </Text>
                  </Space>
                </View>
                <DesktopOnlyView>
                  <Space margin={[6.5, 0, 0, 0]}>
                    <Flex justifyContent="flex-end">
                      <View>
                        <Space margin={[0, 1.5, 0, 0]}>
                          <Button onClick={confirmModal} variant="tertiary">
                            Confirm
                          </Button>
                        </Space>
                        <Button onClick={closeModal}>Cancel</Button>
                      </View>
                    </Flex>
                  </Space>
                </DesktopOnlyView>
                <MobileOnlyView>
                  <Space margin={[5.5, 0, 0, 0]}>
                    <Flex flexDirection="column-reverse">
                      <View>
                        <Size width="100%">
                          <Space margin={[1.5, 0, 0, 0]}>
                            <Button onClick={confirmModal} variant="tertiary">
                              Confirm
                            </Button>
                          </Space>
                        </Size>
                        <Size width="100%">
                          <Button onClick={closeModal}>Cancel</Button>
                        </Size>
                      </View>
                    </Flex>
                  </Space>
                </MobileOnlyView>
              </ContentContainer>
            </Size>
          </Space>
        </Flex>
      </OverlayContainer>
    </Flex>
  );
};

ConfirmationModal.propTypes = {
  couponCode: PropTypes.string.isRequired,
  closeModal: PropTypes.func.isRequired,
  confirmModal: PropTypes.func.isRequired,
};

export default ConfirmationModal;
