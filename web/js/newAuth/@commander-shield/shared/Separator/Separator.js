import React from 'react';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import PropTypes from 'prop-types';
import { CenteredText, SeparatorView, SeparatorBlock } from '../GoogleAuth/GoogleAuthViews';

const Separator = (props) => {
  return (
    <Space margin={[1.75, 0]}>
      <SeparatorView>
        <CenteredText style={props.textViewStyles}>
          <Space padding={[0, 1.75]}>
            <Text {...props}>or</Text>
          </Space>
        </CenteredText>
        <SeparatorBlock />
      </SeparatorView>
    </Space>
  );
};

Separator.propTypes = {
  textViewStyles: PropTypes.object,
};

export default Separator;
