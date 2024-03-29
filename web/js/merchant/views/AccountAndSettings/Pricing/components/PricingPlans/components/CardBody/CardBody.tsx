import React from 'react';
import { connect } from 'react-redux';
import {
  StyledCardBody,
  UnorderedList,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/CardBody.styles';
import { CardBodyPropsT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/CardBody.types';
import { heading } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/data';
import { Flex } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.styles';
import { Text, CheckIcon } from '@razorpay/blade/components';

const CardBody = ({ isMobileResolution, subscriptionPlanData }: CardBodyPropsT): JSX.Element => {
  if (isMobileResolution)
    return (
      <StyledCardBody>
        {subscriptionPlanData?.plan?.details?.feature?.map(({ feature_copy, offering }) => (
          <>
            <CheckIcon color="feedback.icon.positive.intense" size="small" />
            <div data-testid="featureDetail">
              {offering?.includes('\n') ? (
                <>
                  <Text weight="regular" size="large" color="surface.text.gray.muted">
                    {String(feature_copy)}
                  </Text>
                  <UnorderedList>
                    {offering?.split('\n').map((listItems) => (
                      <li key={`${feature_copy}-content-list-${listItems}`}>
                        <Text weight="regular" size="large" color="surface.text.gray.muted">
                          {listItems}
                        </Text>
                      </li>
                    ))}
                  </UnorderedList>
                </>
              ) : (
                <Text weight="regular" size="large" color="surface.text.gray.muted">
                  {String(`${feature_copy} ${offering}`)}
                </Text>
              )}
            </div>
          </>
        ))}
      </StyledCardBody>
    );

  return (
    <>
      <Text weight="semibold" size="large">
        {heading}
      </Text>
      <StyledCardBody>
        {subscriptionPlanData?.plan?.details?.feature?.map(({ feature_copy, offering }) => (
          <Flex key={feature_copy} flexDirection="column" gap={1} data-testid="featureDetail">
            <Text size="medium" weight="regular" color="surface.text.gray.muted">
              {feature_copy}
            </Text>
            {offering?.includes('\n') ? (
              <UnorderedList>
                {offering
                  ?.split('\n')
                  .filter((listItems) => listItems?.length)
                  .map((listItems) => (
                    <li key={`${feature_copy}-content-list-${listItems}`}>
                      <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                        {listItems}
                      </Text>
                    </li>
                  ))}
              </UnorderedList>
            ) : (
              <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                {offering}
              </Text>
            )}
          </Flex>
        ))}
      </StyledCardBody>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    isMobileResolution: state.app.isMobileResolution,
  };
};

export default connect(mapStateToProps, null)(CardBody);
