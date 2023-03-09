import React from 'react';
import { connect } from 'react-redux';
import {
  StyledCardBody,
  UnorderedList,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/CardBody.styles';
import { CardBodyPropsT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/CardBody.types';
import { heading } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/data';
import { Flex } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.styles';
import { Text, Heading, CheckIcon } from '@razorpay/blade/components';

const CardBody = ({ isMobileResolution, subscriptionPlanData }: CardBodyPropsT): JSX.Element => {
  if (isMobileResolution)
    return (
      <StyledCardBody>
        {subscriptionPlanData?.plan?.details?.feature?.map(({ feature_copy, offering }) => (
          <>
            <CheckIcon color="feedback.icon.positive.lowContrast" size="small" />
            <div data-testid="featureDetail">
              {offering?.includes('\n') ? (
                <>
                  <Heading size="small" weight="regular" type="subdued">
                    {String(feature_copy)}
                  </Heading>
                  <UnorderedList>
                    {offering?.split('\n').map((listItems) => (
                      <li key={`${feature_copy}-content-list-${listItems}`}>
                        <Heading size="small" weight="regular" type="subdued">
                          {listItems}
                        </Heading>
                      </li>
                    ))}
                  </UnorderedList>
                </>
              ) : (
                <Heading size="small" weight="regular" type="subdued">
                  {String(`${feature_copy} ${offering}`)}
                </Heading>
              )}
            </div>
          </>
        ))}
      </StyledCardBody>
    );

  return (
    <>
      <Heading size="small" weight="bold">
        {heading}
      </Heading>
      <StyledCardBody>
        {subscriptionPlanData?.plan?.details?.feature?.map(({ feature_copy, offering }) => (
          <Flex key={feature_copy} flexDirection="column" gap={1} data-testid="featureDetail">
            <Text size="medium" type="subdued" weight="regular">
              {feature_copy}
            </Text>
            {offering?.includes('\n') ? (
              <UnorderedList>
                {offering
                  ?.split('\n')
                  .filter((listItems) => listItems?.length)
                  .map((listItems) => (
                    <li key={`${feature_copy}-content-list-${listItems}`}>
                      <Text size="medium" type="subtle" weight="bold">
                        {listItems}
                      </Text>
                    </li>
                  ))}
              </UnorderedList>
            ) : (
              <Text size="medium" type="subtle" weight="bold">
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
