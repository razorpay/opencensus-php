import React from 'react';
import Amount from 'common/ui/Amount';
import Card from 'common/components/Card';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { Text } from '@razorpay/blade/components';
import Space from '@razorpay/blade-old/src/atoms/Space';

import {
  Line,
  FlexV2,
  Wrapper,
  HideMobile,
  ShowMobile,
  HighlightBold,
  TransactionHeader,
  AlertInfoListContainer,
} from 'merchant/views/PaymentHandle/style';
import TextV2 from 'merchant/views/PaymentHandle/components/text';
import { ListFilterPropTypes } from 'merchant/views/PaymentHandle/typings';
import PaymentsList from 'merchant/views/PaymentHandle/views/List/PaymentsList';

const ListFilter: React.FC<ListFilterPropTypes> = ({
  isTestMode,
  handleInfo,
  paymentPageEntity,
}) => {
  return (
    <Space margin={[2, 2, 2, 1.5]}>
      <Wrapper>
        <Card padding={[0]}>
          {isTestMode && (
            <View>
              <AlertInfoListContainer data-testid="ds-test-mode">
                <Text size="small" color="surface.text.gray.subtle">
                  You are in <HighlightBold>Test Mode</HighlightBold>, so only test data is shown.
                  Switch to <u>Live mode</u> to see real transactions data.
                </Text>
              </AlertInfoListContainer>
            </View>
          )}
          <TransactionHeader>
            <HideMobile>
              <Flex alignItems="center">
                <View>
                  <Text weight="semibold">Transactions</Text>
                  <Line />
                  <Space margin={[0, 1.5, 0, 0]}>
                    <View>
                      <TextV2 color="#8991ae" fontSize="12px" text="Total Payments" />
                    </View>
                  </Space>
                  <Text weight="semibold" size="medium" color="surface.text.gray.subtle">
                    {paymentPageEntity.captured_payments_count}
                  </Text>
                  <Line />
                  <Space margin={[0, 1.5, 0, 0]}>
                    <View>
                      <TextV2 color="#8991ae" fontSize="12px" text="Total revenue" />
                    </View>
                  </Space>
                  <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                    <Amount
                      currency={paymentPageEntity.currency}
                      value={paymentPageEntity.total_amount_paid}
                    />
                  </Text>
                  <Line />
                </View>
              </Flex>
            </HideMobile>
            <ShowMobile>
              <Text weight="semibold">Transactions</Text>
              <Space margin={[0.5, 0, 0, 0]}>
                <FlexV2 gap="40px">
                  <FlexV2 gap="24px">
                    <TextV2 color="#8991ae" fontSize="12px" text="Total Payments" />{' '}
                    <Text weight="semibold" color="surface.text.gray.subtle">
                      {paymentPageEntity.captured_payments_count}
                    </Text>
                  </FlexV2>
                  <FlexV2 gap="24px">
                    {' '}
                    <TextV2 color="#8991ae" fontSize="12px" text="Total revenue" />
                    <Amount
                      currency={paymentPageEntity.currency}
                      value={paymentPageEntity.total_amount_paid}
                    />
                  </FlexV2>
                </FlexV2>
              </Space>
            </ShowMobile>
          </TransactionHeader>
          {handleInfo.id && <PaymentsList paymentPageId={handleInfo.id} />}
        </Card>
      </Wrapper>
    </Space>
  );
};

export default ListFilter;
