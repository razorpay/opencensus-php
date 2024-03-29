import React, { useEffect, useState } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import Card from 'common/components/Card';
import lazy from 'merchant/routes/LazyLoader';
import { RZPFeatures } from 'merchant/helpers/data';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import BottomSheet from 'common/components/BottomSheet';
import track from 'merchant/views/PaymentHandle/track';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import TextV2 from 'merchant/views/PaymentHandle/components/text';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { OnboardingPropTypes } from 'merchant/views/PaymentHandle/typings';
import BrandImages from 'merchant/views/PaymentHandle/components/Brandlogo';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import { ONBOARDING_CARD_DEFAULT_PROPS } from 'merchant/views/PaymentHandle/constants';
import {
  Circle,
  LinkCTA,
  LeftPanel,
  RightPanel,
  ShowMobile,
  HideMobile,
  GifContainer,
  SlugContainer,
  LeftContainer,
  SlugImgWrapper,
  BottomImgWrapper,
  EditOutlineWrapper,
  MobileSlugImgContainer,
} from 'merchant/views/PaymentHandle/style';
import PaymentHandleOnboardingLottie from 'merchant/helpers/lottieConfigs/PaymentHandleOnboarding.json';

const CustomLottie = lazy(
  () =>
    import(
      /* webpackChunkName: 'CustomLottiePaymentHandleOnboardingLottie' */ 'common/new-ui/Lottie'
    ),
);

const EditPaymentHandle = lazy(
  () =>
    import(
      /* webpackChunkName: 'EditPaymentHandle' */ 'merchant/views/PaymentHandle/views/Onboarding/EditPaymentHandle'
    ),
);

const Onboarding: React.FC<OnboardingPropTypes> = ({
  openModal,
  closeModal,
  handleInfo,
  setOnboardingVisible,
}) => {
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);

  useEffect(() => {
    track.onboarding.startSuccess();
  }, []);

  const bottomSheetTrigger = (
    <Flex alignItems="center" justifyContent="flex-end">
      <SlugImgWrapper>
        <EditOutlineWrapper>
          <i className="i i-edit-outline" />
        </EditOutlineWrapper>
        <Text weight="bold" type="subtle" contrast="low" color="primary.800">
          Edit
        </Text>
      </SlugImgWrapper>
    </Flex>
  );

  const showEditModal = () => {
    openModal({
      size: 'small',
      component: (
        <SuspenseWithLoader>
          <EditPaymentHandle onCancelClick={closeModal} handleInfo={handleInfo} />
        </SuspenseWithLoader>
      ),
    });
  };

  const handleClose = () => {
    setIsBottomSheetOpen(false);
  };

  const setPHOnboarding = () => {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.PH,
      data: {
        isEnabled: true,
        lastVisitedTime: Date.now(),
      },
    });
    setOnboardingVisible(false);
    track.onboarding.getStarted();
  };

  return (
    <View>
      <Space padding={[5, 4, 0, 3]}>
        <Flex>
          <View>
            <Size width="50%">
              <LeftContainer>
                <Card {...ONBOARDING_CARD_DEFAULT_PROPS} backgroundColor="background.400">
                  <View>
                    <BrandImages />
                  </View>
                  <LeftPanel>
                    <GifContainer>
                      <SuspenseWithLoader>
                        <CustomLottie
                          loop
                          autoplay
                          isStopped={false}
                          animationData={PaymentHandleOnboardingLottie}
                        />
                      </SuspenseWithLoader>
                    </GifContainer>
                  </LeftPanel>
                  <BottomImgWrapper>
                    <BrandImages />
                  </BottomImgWrapper>
                </Card>
              </LeftContainer>
            </Size>
            <View>
              <Card {...ONBOARDING_CARD_DEFAULT_PROPS}>
                <RightPanel>
                  <View>
                    <TextV2
                      color="#132644"
                      fontSize="28px"
                      fontWeight="700"
                      mobileFontSize="22px"
                      mobileFontWeight="700"
                      text="Introducing Razorpay.me"
                    />
                  </View>
                  <Space margin={[1, 0, 2.5, 0]}>
                    <View>
                      <TextV2
                        fontSize="14px"
                        color="#435775"
                        fontWeight="400"
                        mobileFontSize="16px"
                        text="A unique link for your business that you and your customers can remember"
                      />
                    </View>
                  </Space>
                  <SlugContainer>
                    <Flex justifyContent="space-between" alignItems="center">
                      <Size height="100%">
                        <Space padding={[0, 2]}>
                          <View>
                            <Flex>
                              <View>
                                {' '}
                                <Text type="subtle" contrast="low" size="medium">
                                  razorpay.me/
                                </Text>
                                <Text weight="bold" type="normal" contrast="low" size="medium">
                                  {handleInfo.slug}
                                </Text>
                              </View>
                            </Flex>
                            <HideMobile>
                              <Flex alignItems="center">
                                <SlugImgWrapper onClick={showEditModal}>
                                  <EditOutlineWrapper>
                                    <i className="i i-edit-outline" />
                                  </EditOutlineWrapper>
                                  <Text
                                    weight="bold"
                                    contrast="low"
                                    color="primary.800"
                                    data-testid="desktop-edit"
                                  >
                                    Edit
                                  </Text>
                                </SlugImgWrapper>
                              </Flex>
                            </HideMobile>
                          </View>
                        </Space>
                      </Size>
                    </Flex>
                  </SlugContainer>
                  <MobileSlugImgContainer>
                    <ShowMobile>
                      <BottomSheet
                        isControlled
                        isBlocking={false}
                        trigger={bottomSheetTrigger}
                        onDismiss={handleClose}
                        isOpen={isBottomSheetOpen}
                        onTriggerClick={() => setIsBottomSheetOpen(true)}
                      >
                        {' '}
                        <EditPaymentHandle
                          isBottomSheet
                          handleInfo={handleInfo}
                          onCancelClick={handleClose}
                        />
                      </BottomSheet>
                    </ShowMobile>
                  </MobileSlugImgContainer>
                  <LinkCTA>
                    <Text weight="bold" type="subtle" contrast="low">
                      With this link, you can:
                    </Text>
                  </LinkCTA>
                  <Space margin={[0, 0, 1.5, 0]}>
                    <View>
                      <Flex alignItems="center">
                        <View>
                          <Flex alignItems="center" justifyContent="center">
                            <Circle>
                              <i className="i-user-circle" />
                            </Circle>
                          </Flex>
                          <TextV2
                            fontSize="14px"
                            color="#435775"
                            fontWeight="400"
                            mobileFontSize="16px"
                            text="Accept payments from multiple customers"
                          />
                        </View>
                      </Flex>
                    </View>
                  </Space>
                  <View>
                    <Flex alignItems="center">
                      <View>
                        <Flex alignItems="center" justifyContent="center">
                          <Circle>
                            <i className="i-rupee" />
                          </Circle>
                        </Flex>
                        <TextV2
                          fontSize="14px"
                          color="#435775"
                          fontWeight="400"
                          mobileFontSize="16px"
                          text="Customers can pay any amount via this link"
                        />
                      </View>
                    </Flex>
                  </View>
                  <Size width="100%">
                    <View>
                      <Space margin={[5.5, 0, 0, 0]}>
                        <Size width="100%">
                          <Button.Primary onClick={setPHOnboarding} data-testid="ph-onboarding-btn">
                            Get Started <i className="i i-arrow-forward" />
                          </Button.Primary>
                        </Size>
                      </Space>
                    </View>
                  </Size>
                </RightPanel>
              </Card>
            </View>
          </View>
        </Flex>
      </Space>
    </View>
  );
};

export default compose(
  connect(
    (state) => ({
      handleInfo: state.paymentHandle.handleInfo.data,
    }),
    {
      openModal,
      closeModal,
    },
  ),
)(Onboarding);
