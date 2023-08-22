import React, { useState } from 'react';
import { connect } from 'react-redux';
import {
  EqualWidth,
  NoticeWrapper,
  EditContainer,
  EditPHBtnWrapper,
  EditSlugContainer,
} from 'merchant/views/PaymentHandle/style';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { Text, Button } from '@razorpay/blade/components';
import track from 'merchant/views/PaymentHandle/track';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import { editSlugApi } from 'merchant/reducers/paymentHandle/api';
import TextV2 from 'merchant/views/PaymentHandle/components/text';
import { isStringAlphabetAndNumberOnly } from 'common/utils/rzp-utils';
import { debounce } from 'merchant/views/onboarding/mobile/services/utils';
import { EditSlugModalPropTypes } from 'merchant/views/PaymentHandle/typings';
import Suggestion from 'merchant/views/PaymentHandle/views/Onboarding/Suggestion';
import { fetchPaymentHandle as getPaymentHandleDetails } from 'merchant/reducers/paymentHandle';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';

const EditSlugModal = ({
  handleInfo,
  isBottomSheet,
  onCancelClick,
  showNotification,
  fetchPaymentHandle,
}: EditSlugModalPropTypes): JSX.Element => {
  const previousSlug = handleInfo.slug?.replace(/@/g, '') || '';
  const [slug, setSlug] = useState(previousSlug);

  const onInputChange = debounce((val) => {
    setSlug(val);
  }, 200);

  const onCancelModalClick = () => {
    onCancelClick();
    track.onboarding.editLinkCancel();
  };

  const updateMerchantSlug = () => {
    const paymentHandle = `@${slug}`;
    return editSlugApi(paymentHandle)
      .then(async () => {
        showNotification({
          type: 'success',
          message: `Your payment handle has been successfully updated to @${slug}`,
          hidePrevious: true,
        });
        await fetchPaymentHandle();
        onCancelClick();
        track.onboarding.editLinkSuccess();
        track.onboarding.editLinkSave();
      })
      .catch((_err) => {
        showNotification({
          type: 'error',
          message: _err.errors[0],
          hidePrevious: true,
        });
      });
  };

  const isMinimumSlugLength = slug.length > 4;
  const isSuggestionAvailable = previousSlug !== slug && isMinimumSlugLength;
  const isSaveBtnDisbaled = isStringAlphabetAndNumberOnly(slug) || !isMinimumSlugLength;
  return (
    <EditSlugContainer>
      <View>
        <Flex alignItems="center" justifyContent="space-between">
          <View>
            {' '}
            <TextV2
              color="#162F56"
              fontSize="16px"
              fontWeight={700}
              mobileFontSize="20px"
              text="Edit your Razorpay.me link"
            />
            {!isBottomSheet && <i className="i i-close cursor-pointer" onClick={onCancelClick} />}
          </View>
        </Flex>
        <Space margin={[2, 0, 2, 0]}>
          <EditContainer>
            {' '}
            <TextInput
              autoFocus
              value={slug}
              width="auto"
              label=""
              prefix="razorpay.me/@"
              onChange={onInputChange}
            />
            <View>
              <Space margin={[0.5, 0, 1, 0]}>
                <View>
                  {isMinimumSlugLength && (
                    <View>
                      {isStringAlphabetAndNumberOnly(slug) ? (
                        <TextV2
                          color="#F05050"
                          fontSize="12px"
                          text="Only alphabets & numbers are allowed"
                        />
                      ) : (
                        isSuggestionAvailable && (
                          <Space margin={[1, 0]}>
                            <Suggestion slug={slug} />
                          </Space>
                        )
                      )}
                    </View>
                  )}
                </View>
              </Space>
            </View>
          </EditContainer>
        </Space>
        <NoticeWrapper>
          <Flex>
            <View>
              <Space margin={[0, 1, 0, 0]}>
                <View>
                  {' '}
                  <img
                    alt="alert"
                    width="16px"
                    height="16px"
                    src="https://cdn.razorpay.com/static/assets/instrument-request/alert-triangle.svg"
                  />
                </View>
              </Space>
              <View>
                <Text type="subtle" contrast="low" size="xsmall">
                  Don’t forget to share your updated Razorpay.me link with your customers
                </Text>
              </View>
            </View>
          </Flex>
        </NoticeWrapper>
        <Space margin={[3, 0, 0, 0]}>
          <EditPHBtnWrapper>
            <EqualWidth>
              <Button variant="secondary" isFullWidth={true} onClick={onCancelModalClick}>
                Cancel
              </Button>
            </EqualWidth>
            <EqualWidth>
              <Button
                variant="primary"
                isFullWidth={true}
                onClick={updateMerchantSlug}
                isDisabled={!!isSaveBtnDisbaled}
              >
                Save
              </Button>
            </EqualWidth>
          </EditPHBtnWrapper>
        </Space>
      </View>
    </EditSlugContainer>
  );
};

export default connect(null, {
  showNotification: showNotificationProp,
  fetchPaymentHandle: getPaymentHandleDetails,
})(EditSlugModal);
