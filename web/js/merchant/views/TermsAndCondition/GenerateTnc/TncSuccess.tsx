import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Card from 'common/components/Card';
import Illustration from './Illustration.svg';
import { StyledFooter, Image, LinkText, StyledText, TncLink } from './Styled';

interface TncSuccessLinkPropsT {
  history: any;
  data: any;
}

const TncSuccessLink: React.FC<TncSuccessLinkPropsT> = ({ history, data }) => {
  return (
    <View>
      <Space margin={[2.5, 0, 5.5]}>
        <View>
          <Image src={Illustration} />
        </View>
      </Space>

      <Card>
        <Space padding={[1]}>
          <View>
            <Flex alignItems="center" justifyContent="center">
              <View>
                <TncLink href={data.link} target="_blank" rel="noreferrer noopener">
                  <Text size="medium" weight="bold" color="primary.800">
                    {data.link}
                  </Text>
                </TncLink>
                <Space margin={[0, 0, 0, 1]}>
                  <Flex>
                    <View>
                      <Icon name="link" size="small" fill="primary.800" />{' '}
                    </View>
                  </Flex>
                </Space>
              </View>
            </Flex>
            <View>
              <Space padding={[2, 0, 0]} margin={[1.5, 0, 0]}>
                <LinkText color="shade.960" size="medium" align="center">
                  You can make changes to this page from My account section in the web dashboard
                </LinkText>
              </Space>
            </View>
          </View>
        </Space>
      </Card>

      <Space margin={[1.75, 0, 0]}>
        <StyledText>
          <Space padding={[1.5, 3.125]}>
            <Text size="small" align="center">
              This page link will be displayed on the apps that you would use to accept payments.
              (payment links, payment pages etc )
            </Text>
          </Space>
        </StyledText>
      </Space>

      <Flex justifyContent="space-between">
        <StyledFooter>
          <Button type="button" size="large" onClick={() => history.push('/')} block>
            Okay, got it
          </Button>
        </StyledFooter>
      </Flex>
    </View>
  );
};

export default TncSuccessLink;
