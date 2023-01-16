import React from 'react';
import styled from 'styled-components';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import ErrorIcon from 'merchant/views/onboarding/mobile/Step/Icons/error.svg';

type AlignType = 'left' | 'right';
interface InfoPropsT {
  title: string;
  description: string | React.ReactNode;
  titleColor?: string;
  descriptionColor?: string;
  hasError?: boolean;
  titleJSX?: React.ReactNode;
  titleJSXAlign?: AlignType;
  descriptionJSX?: React.ReactNode;
  descriptionJSXAlign?: AlignType;
  isNewNCEnabled?: boolean;
}

const DescriptionElement = styled(View)`
  display: inline;
`;

const TitleElement = styled(View)`
  color: ${({ theme, color }) => getColor(theme, color)};
`;

const Info: React.FC<InfoPropsT> = ({
  title,
  description,
  titleColor = 'shade.970',
  descriptionColor = 'shade.960',
  hasError = false,
  titleJSX,
  descriptionJSX,
  titleJSXAlign = 'right',
  descriptionJSXAlign = 'right',
  isNewNCEnabled = false,
}) => {
  return (
    <>
      <Space margin={[0, 0, 0.5, 0]}>
        <Flex>
          <View>
            {titleJSXAlign === 'left' && titleJSX ? (
              <Space margin={[0.5, 0.75, 0, 0]}>
                <Flex alignSelf="baseline">
                  <View>{titleJSX}</View>
                </Flex>
              </Space>
            ) : null}
            <Text size={isNewNCEnabled ? 'large' : 'medium'} color={titleColor} weight="bold">
              {title}
            </Text>
            {titleJSXAlign === 'right' && titleJSX ? (
              <Space margin={[0.5, 0, 0, 0.75]}>
                <Flex alignSelf="baseline">
                  <TitleElement color={titleColor}>{titleJSX}</TitleElement>
                </Flex>
              </Space>
            ) : null}
            {hasError ? (
              <Space margin={[0, 0, 0, 1.25]}>
                <Flex alignItems="center">
                  <View>
                    <img src={ErrorIcon} alt="error" />
                  </View>
                </Flex>
              </Space>
            ) : null}
          </View>
        </Flex>
      </Space>
      <Text size={isNewNCEnabled ? 'medium' : 'xsmall'} color={descriptionColor}>
        {description}
        {descriptionJSXAlign === 'right' && descriptionJSX ? (
          <Space margin={[0, 0, 0, 0.5]}>
            <DescriptionElement>{descriptionJSX}</DescriptionElement>
          </Space>
        ) : null}
      </Text>
    </>
  );
};

export default Info;
