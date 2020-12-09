import React from 'react';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import ErrorIcon from '../Step/Icons/error.svg';

interface InfoPropsT {
  title: string;
  description: string;
  titleColor?: string;
  descriptionColor?: string;
  hasError?: boolean;
}

const Info: React.FC<InfoPropsT> = ({
  title,
  description,
  titleColor = 'shade.970',
  descriptionColor = 'shade.960',
  hasError = false,
}) => {
  return (
    <>
      <Space margin={[0, 0, 0.5, 0]}>
        <Flex>
          <View>
            <Text size="medium" color={titleColor} weight="bold">
              {title}
            </Text>
            {hasError ? (
              <Space margin={[0, 0, 0, 1.25]}>
                <View>
                  <img src={ErrorIcon} alt="error" />
                </View>
              </Space>
            ) : null}
          </View>
        </Flex>
      </Space>
      <Text size="xsmall" color={descriptionColor}>
        {description}
      </Text>
    </>
  );
};

export default Info;
