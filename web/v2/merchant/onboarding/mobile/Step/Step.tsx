import React from 'react';
import styled from 'styled-components';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import LockedIcon from './Icons/locked.svg';
import CompletedIcon from './Icons/completed.svg';
import ErrorIcon from './Icons/error.svg';
import PendingIcon from './Icons/pending.svg';

export interface StepPropsT {
  name: string;
  id: string;
  hasErrorText?: string;
  isComplete?: boolean;
  isLocked?: boolean;
  onClick: (id: string) => void;
}

const styles = {
  stepContainer: {
    borderColor({ theme, hasErrorText, isLocked }) {
      if (isLocked) {
        return getColor(theme, 'shade.930');
      }
      if (hasErrorText) {
        return getColor(theme, 'negative.900');
      }
      return getColor(theme, 'primary.400');
    },
  },
  name: {
    color({ isLocked }) {
      if (isLocked) {
        return 'shade.950';
      }
      return 'shade.970';
    },
  },
};

const StepContainer = styled(View)`
  border: 1px solid ${styles.stepContainer.borderColor};
  border-radius: 4px;
  cursor: ${(props) => (props.isLocked ? 'not-allowed' : 'pointer')};
`;

const StatusIndicatorIcon = ({ isComplete, hasErrorText, isLocked }) => {
  let icon = PendingIcon;
  let alt = 'pending';

  if (isLocked) {
    icon = LockedIcon;
    alt = 'locked';
  }

  if (hasErrorText) {
    icon = ErrorIcon;
    alt = 'error';
  }

  if (isComplete) {
    icon = CompletedIcon;
    alt = 'completed';
  }

  return <img src={icon} alt={alt} />;
};

const Step: React.FC<StepPropsT> = ({
  id,
  name,
  hasErrorText = '',
  isComplete = false,
  isLocked = false,
  onClick,
}) => {
  const _onClick = () => {
    onClick(id);
  };

  return (
    <Flex flexDirection="column">
      <View>
        <Flex flex={1} alignItems="center">
          <Space padding={[1.5, 2]}>
            <StepContainer onClick={_onClick} hasErrorText={hasErrorText} isLocked={isLocked}>
              <Size height={1.5} width={1.5}>
                <Space margin={[0, 1, 0, 0]}>
                  <Flex>
                    <View>
                      <StatusIndicatorIcon
                        isComplete={isComplete}
                        isLocked={isLocked}
                        hasErrorText={hasErrorText}
                      />
                    </View>
                  </Flex>
                </Space>
              </Size>
              <Flex flex={1}>
                <Text size="medium" color={styles.name.color({ isLocked })}>
                  {name}
                </Text>
              </Flex>
              {!isLocked ? <Icon name="chevronRight" size="small" fill="shade.980" /> : null}
            </StepContainer>
          </Space>
        </Flex>
        {hasErrorText ? (
          <Space margin={[0.5, 0, 0, 0]}>
            <Text color="negative.900" size="xxsmall">
              {hasErrorText}
            </Text>
          </Space>
        ) : null}
      </View>
    </Flex>
  );
};

export default Step;
