import React, { useState } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import View from '@razorpay/blade-old/src/atoms/View';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import TncForm from './TncForm';
import TncSuccessModal from './TncSuccess';
import { StyledContent, StyledHeader } from './Styled';

const GenerateTncPage: React.FC<RouteComponentProps> = ({ history }) => {
  const [data, setData] = useState(null);

  const setApiResponse = (response) => {
    setData(response);
  };

  return (
    <View>
      <Space padding={[2, 1.5, 1.25, 0.75]}>
        <Flex justifyContent="space-between" alignItems="center">
          <StyledHeader>
            <Flex flexDirection="row">
              <View>
                {!data ? (
                  <>
                    <Space padding={[0, 0.5, 0, 0]}>
                      <View onClick={() => history.push('/')}>
                        <Icon name="chevronLeft" size="large" fill="shade.800" />
                      </View>
                    </Space>
                    <View>
                      <Heading size="large">Terms and Conditions</Heading>
                    </View>
                  </>
                ) : (
                  <Space margin={[0, 0, 0, 1.25]}>
                    <View>
                      <Heading size="large">TnC link has been generated 🎉</Heading>
                    </View>
                  </Space>
                )}
              </View>
            </Flex>
          </StyledHeader>
        </Flex>
      </Space>

      <Space margin={[0, 0, 8.75, 0]} padding={[1, 2, 2]}>
        <StyledContent>
          {!data ? (
            <>
              <Text color="shade.950" size="xsmall" _lineHeight="medium">
                Give us these details to generate your Terms and conditions page
              </Text>
              <Space margin={[2, 0, 0]}>
                <View>
                  <TncForm setApiResponse={setApiResponse} />
                </View>
              </Space>
            </>
          ) : (
            <TncSuccessModal history={history} data={data} />
          )}
        </StyledContent>
      </Space>
    </View>
  );
};

export default withRouter<any>(GenerateTncPage);
