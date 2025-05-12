import React, { useMemo } from 'react';
import {
  Box,
  StepGroup,
  StepItem,
  StepItemIndicator,
  StepItemIcon,
  CheckIcon,
  Heading,
  Badge,
} from '@razorpay/blade/components';
import { RESUME_COMPANY_REG, UserJourneyProgress } from '../constant';

interface UpdateJourneyType {
  title: string;
  marker: JSX.Element;
  trailing?: JSX.Element;
}
const UserJourney = ({ userJourney, isSmallDevice }) => {
  let isActive = true;
  const updateJourney: UpdateJourneyType[] = useMemo(
    () =>
      UserJourneyProgress.map((eachScreen) => {
        if (eachScreen.screen === userJourney && isActive) {
          isActive = false;
          return {
            ...eachScreen,
            marker: <StepItemIndicator color="primary" />,
            trailing: (
              <Badge color="primary" emphasis="subtle" marginLeft="spacing.3" size="medium">
                Up Next
              </Badge>
            ),
          };
        } else if (!isActive) {
          return {
            ...eachScreen,
            marker: <StepItemIndicator color="neutral" />,
          };
        } else {
          return {
            ...eachScreen,
            marker: <StepItemIcon icon={CheckIcon} color="positive" />,
          };
        }
      }),
    UserJourneyProgress,
  );

  return (
    <Box
      borderRadius="large"
      marginX={isSmallDevice ? 'spacing.0' : 'spacing.7'}
      marginY={'spacing.7'}
      padding="spacing.7"
      paddingBottom="spacing.4"
      backgroundColor="surface.background.gray.intense"
      display="flex"
      flexDirection="column"
      justifyContent="flex-start"
    >
      <Heading marginBottom="spacing.4">{RESUME_COMPANY_REG}</Heading>
      <StepGroup orientation="vertical" size="medium">
        {updateJourney.map((eachScreen) => {
          return (
            <StepItem
              key={eachScreen.title}
              title={eachScreen.title}
              marker={eachScreen.marker}
              trailing={eachScreen?.trailing}
            />
          );
        })}
      </StepGroup>
    </Box>
  );
};
export default UserJourney;
