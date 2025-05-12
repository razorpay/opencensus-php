import React, { useEffect } from 'react';
import { Box, Card, CardBody, Heading, Text } from '@razorpay/blade/components';
import { TRACK_PROGRESS } from '../constant';
import StepsItem from './StepsItem';
import { trackEventOnUserStatusPageView } from '../analytics';
import { StatusStepsType } from '../types';

const UserIncorporationStatus = ({
  isSmallDevice,
  getSteps,
}: {
  isSmallDevice: boolean;
  getSteps: () => StatusStepsType[];
}) => {
  useEffect(() => {
    trackEventOnUserStatusPageView();
  }, []);
  return (
    <Card
      borderRadius="large"
      marginTop="spacing.7"
      marginX={{ base: 'spacing.0', m: 'spacing.7' }}
      marginBottom="spacing.7"
    >
      <CardBody>
        <Box
          display="flex"
          borderRadius="large"
          flexDirection="column"
          justifyContent="flex-start"
          backgroundColor="surface.background.gray.intense"
        >
          <Heading marginBottom="spacing.7">{TRACK_PROGRESS}</Heading>
          <Box display="flex" flexDirection="column">
            {getSteps().map((item) => {
              return (
                <StepsItem
                  key={item.title}
                  icon={item.icon}
                  title={item.title}
                  status={item.status}
                />
              );
            })}
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};
export default UserIncorporationStatus;
