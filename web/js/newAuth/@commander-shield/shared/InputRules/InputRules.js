import React from 'react';
import PropTypes from 'prop-types';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import styled, { css, keyframes } from 'styled-components';
import TickIcon from '../../assets/TickIcon';

/** Rule states for each rule. */
const RULE_STATES = {
  ERROR: 'error',
  SUCCESS: 'success',
  NEUTRAL: 'neutral',
};

/** Colors used for visual feedback of the rules */
const RULE_COLORS = {
  NEGATIVE: 'negative.900',
  POSITIVE: 'positive.900',
  NEUTRAL: 'shade.950',
};

/** Get color according to the rule state */
const getStateColor = (state) => {
  switch (state) {
    case RULE_STATES.ERROR:
      return RULE_COLORS.NEGATIVE;

    case RULE_STATES.SUCCESS:
      return RULE_COLORS.POSITIVE;

    default:
      return RULE_COLORS.NEUTRAL;
  }
};

/** Bounce animation for visual feedback on the rule checkmarks. */
const bounce = keyframes`
  0% {
    transform: scale(1);
  }

  25% {
    transform: scale(1.5);
  }

  100% {
    transform: scale(1);
  }
`;

const BounceCheckIcon = styled(Text)`
  animation: ${(props) =>
    props.animate
      ? css`
          ${bounce} 0.4s ease-in-out
        `
      : `null`};
`;

const Container = styled(View)`
  gap: 8px;
`;

const InputRule = ({ description, currentValue, touched, pattern }) => {
  const [ruleState, setRuleState] = React.useState(RULE_STATES.NEUTRAL);

  /** Realtime feedback on the current value. Triggers only after input touched. */
  React.useEffect(() => {
    if (touched) {
      if (pattern.test(currentValue)) {
        setRuleState(RULE_STATES.SUCCESS);
      } else {
        setRuleState(RULE_STATES.ERROR);
      }
    }
  }, [currentValue, touched, pattern]);

  return (
    <Flex flexGrow={1} alignItems="center">
      <Container>
        <BounceCheckIcon
          key={ruleState}
          color={getStateColor(ruleState === RULE_STATES.ERROR ? RULE_STATES.NEUTRAL : ruleState)}
          animate={touched}
        >
          <TickIcon />
        </BounceCheckIcon>
        <Text size="small" color={getStateColor(ruleState)}>
          {description}
        </Text>
      </Container>
    </Flex>
  );
};

InputRule.propTypes = {
  /** Rule description to be shown to the user. */
  description: PropTypes.string,
  /** Current state value of the input to use as source. */
  currentValue: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  /**  Current state value of the input touched flag. */
  touched: PropTypes.bool,
  /** Regex pattern to test rule validity. */
  pattern: PropTypes.instanceOf(RegExp).isRequired,
};

InputRule.defaultProps = {
  description: '',
  touched: false,
};

const RuleList = styled.ul`
  display: flex;
  flex-direction: column;
  gap: 4px;
  list-style-type: none;
  padding: 0;
`;

const InputRules = ({ value, rules, touched }) => {
  return (
    <RuleList>
      {rules.map((rule) => {
        return (
          <li key={rule.id}>
            <InputRule
              description={rule.description}
              currentValue={value}
              touched={touched}
              pattern={rule.pattern}
            />
          </li>
        );
      })}
    </RuleList>
  );
};

InputRules.propTypes = {
  /** Current state value of the input to use as source. */
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  /** Current state value of the input touched flag. */
  touched: PropTypes.bool,
  /** Array of rules. This defines the order and text to be shown as rules. */
  rules: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string.isRequired,
      description: PropTypes.string,
      pattern: PropTypes.instanceOf(RegExp).isRequired,
    }),
  ),
};

InputRules.defaultProps = {
  touched: false,
  rules: [],
};

export default InputRules;
