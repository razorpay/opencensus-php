import React, { useState, useEffect } from 'react';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { getSlugSuggestions, getSlugAvailability } from 'merchant/views/PaymentHandle/utils';
import {
  RedCheck,
  GreenCheck,
  InfoWrapper,
  InlineMessage,
  TextSuggestion,
} from 'merchant/views/PaymentHandle/style';

const Suggestion: React.FC<{
  slug: string;
}> = ({ slug }) => {
  const [suggestions, setSuggestions] = useState([]);
  const [isExisting, setIsExisting] = useState(false);
  const lastElement = suggestions.length - 1;

  useEffect(() => {
    getSlugAvailability(slug).then(setIsExisting);
    if (isExisting) {
      getSlugSuggestions().then(setSuggestions);
    }
  }, [slug, isExisting]);

  return (
    <>
      <InfoWrapper>
        {!isExisting ? (
          <GreenCheck className="i-check-circle" />
        ) : (
          <RedCheck className="i-close-circle" />
        )}
        <Space margin={[0, 0.25, 0, 0.25]}>
          <Text color="primary.700" weight="bold" size="xsmall">
            @{slug}
          </Text>
        </Space>
        <Text color="shade.960" size="small">
          {!isExisting ? 'is' : 'is not'} available
        </Text>
      </InfoWrapper>
      {isExisting && (
        <View>
          <TextSuggestion>
            {suggestions?.length > 0 && <InlineMessage>However</InlineMessage>}
            {suggestions?.map((suggestion, _index) => {
              return (
                <>
                  <Text color="primary.700" weight="bold" size="xsmall" key={_index}>
                    {suggestion}
                    {lastElement !== _index && ','}
                  </Text>
                  {lastElement === _index && <InlineMessage> are available</InlineMessage>}
                </>
              );
            })}
          </TextSuggestion>
        </View>
      )}
    </>
  );
};

export default Suggestion;
