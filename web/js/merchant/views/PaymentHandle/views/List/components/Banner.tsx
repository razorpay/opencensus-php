import React, { useState } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import Card from 'common/components/Card';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import {
  Wrapper,
  HideMobile,
  ShowMobile,
  CTAWrapper,
  NewContainer,
  BottomWrapper,
  InfoContainer,
  ButtonWrapper,
  AlertContainer,
  ShareBtnWrapper,
  BannerSlugContainer,
  BlurFilterContainer,
  IconBackgroundWrapper,
} from 'merchant/views/PaymentHandle/style';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import { Text, Button } from '@razorpay/blade/components';
import track from 'merchant/views/PaymentHandle/track';
import Space from '@razorpay/blade-old/src/atoms/Space';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { BannerProps } from 'merchant/views/PaymentHandle/typings';
import { showNotification } from 'merchant_common/reducers/notifications';
import BrandImages from 'merchant/views/PaymentHandle/components/Brandlogo';
import PaymentHandleModal from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentHandleModal';

const Banner: React.FC<BannerProps> = ({
  handleInfo,
  isTestMode,
  isMobile,
  openModal,
  closeModal,
}) => {
  const mobileBtnWidth = isMobile ? '32%' : '22%';
  const [paymentHandleConfig] = useState({
    paymentHandleSlug: handleInfo.slug ?? '@',
    paymentHandleUrl: handleInfo.url ?? `https://razorpay.me/@`,
  });

  const openPHShareModal = () => {
    openModal({
      component: (
        <PaymentHandleModal
          product="PH"
          closeModal={closeModal}
          showNotification={showNotification}
          paymentHandleData={paymentHandleConfig}
        />
      ),
      size: 'medium',
      className: 'PaymentHandle--Modal',
    });
    track.detail.shareLink();
  };

  return (
    <Space margin={[2, 2, 2, 1.5]}>
      <Wrapper>
        <Card padding={[0]}>
          <InfoContainer data-testid="left-container">
            <Flex justifyContent="space-between">
              <Size height="100%">
                <View>
                  <HideMobile>
                    <View>
                      <BrandImages isGreyScreenVisible={true} />
                    </View>
                  </HideMobile>
                  <Flex alignItems="center" justifyContent="center">
                    <Size width="480px">
                      <Flex alignItems="baseline">
                        <View>
                          <HideMobile>
                            <View>
                              <NewContainer>NEW</NewContainer>
                            </View>
                          </HideMobile>
                          <Space padding={isMobile ? [2.5, 0, 2.5, 2.5] : [2.5]}>
                            <View>
                              <View>
                                <Flex>
                                  <View>
                                    <HideMobile>
                                      {' '}
                                      <Text
                                        weight="semibold"
                                        size="medium"
                                        color="surface.text.gray.normal"
                                      >
                                        Get paid instantly with your Razorpay.me link
                                      </Text>
                                    </HideMobile>
                                    <ShowMobile>
                                      <Text size="medium" color="surface.text.gray.normal">
                                        Get paid instantly with your
                                      </Text>
                                      <Text
                                        weight="semibold"
                                        size="medium"
                                        color="surface.text.gray.subtle"
                                      >
                                        Razorpay.me link
                                      </Text>
                                    </ShowMobile>
                                  </View>
                                </Flex>
                                <CTAWrapper>
                                  <Text size="medium" color="surface.text.gray.subtle">
                                    Share your Razorpay.me link with customers as many times as you
                                    need to accept payments
                                  </Text>
                                </CTAWrapper>
                                <BannerSlugContainer>
                                  <Flex justifyContent="space-between" alignItems="center">
                                    <Size height="100%">
                                      <Space padding={[0, 2]}>
                                        <BlurFilterContainer isTestMode={isTestMode}>
                                          <Flex>
                                            <View>
                                              {' '}
                                              <Text size="medium" color="surface.text.gray.subtle">
                                                razorpay.me/
                                              </Text>
                                              <Text
                                                weight="semibold"
                                                size="medium"
                                                color="surface.text.gray.normal"
                                              >
                                                {handleInfo.slug}
                                              </Text>
                                            </View>
                                          </Flex>
                                        </BlurFilterContainer>
                                      </Space>
                                    </Size>
                                  </Flex>
                                </BannerSlugContainer>
                                {!isTestMode && (
                                  <ButtonWrapper>
                                    <Size width={mobileBtnWidth}>
                                      <CustomClipboard value={`razorpay.me/${handleInfo.slug}`}>
                                        {' '}
                                        <Button variant="secondary" onClick={track.detail.copyLink}>
                                          Copy Link
                                        </Button>
                                      </CustomClipboard>
                                    </Size>
                                    <ShareBtnWrapper>
                                      <Button
                                        variant="primary"
                                        isFullWidth={true}
                                        onClick={openPHShareModal}
                                      >
                                        Share
                                      </Button>
                                    </ShareBtnWrapper>
                                  </ButtonWrapper>
                                )}
                                {isTestMode && (
                                  <Space margin={[3, 0, 5, 0]}>
                                    <View>
                                      {' '}
                                      <AlertContainer>
                                        <IconBackgroundWrapper>
                                          <i className="i i-triangle-alert" />
                                        </IconBackgroundWrapper>
                                        <Text
                                          weight="semibold"
                                          size="medium"
                                          color="surface.text.gray.subtle"
                                        >
                                          Switch to <u>Live mode</u> to get your Razorpay.me link
                                        </Text>
                                      </AlertContainer>
                                    </View>
                                  </Space>
                                )}
                              </View>
                            </View>
                          </Space>
                        </View>
                      </Flex>
                    </Size>
                  </Flex>
                  <BottomWrapper>
                    <BrandImages isGreyScreenVisible={true} />
                  </BottomWrapper>
                </View>
              </Size>
            </Flex>
          </InfoContainer>
        </Card>
      </Wrapper>
    </Space>
  );
};

export default compose(
  connect(null, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
    showNotification,
  }),
)(Banner);
