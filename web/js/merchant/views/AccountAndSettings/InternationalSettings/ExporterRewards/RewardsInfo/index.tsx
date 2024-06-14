import React from 'react';
import { Box, Heading, Text, List, ListItem } from '@razorpay/blade/components';
import styled from 'styled-components';

const Container = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(560px, 1fr));
  grid-gap: 1.5rem;

  @media screen and (max-width: ${({ theme }) => theme.breakpoints.l}px) {
    grid-template-columns: 1fr;
    grid-gap: 1rem;
  }
`;

const Card = styled.div(
  ({ theme }) => `
    position: relative;
    background-color: ${theme.colors.surface.background.gray.intense};
    border-radius: ${theme.border.radius.large}px;
    padding: ${theme.spacing[7]}px;
    overflow: hidden;
    z-index: 1;
  `,
);

const ImageContainer = styled.div`
  width: 160px;
  height: 160px;
  background: url(${({ url }) => url}) no-repeat center center / cover;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.moderate};
  border-radius: ${({ theme }) => theme.border.radius.round};

  @media screen and (max-width: ${({ theme }) => theme.breakpoints.s}px) {
    position: absolute;
    right: -16px;
    top: -24px;
    z-index: -1;
    opacity: 0.25;
  }
`;

const RewardsInfo = () => {
  return (
    <Container>
      <Card>
        <Heading marginBottom="spacing.6">Rewards - Fee Credits</Heading>
        <Box display="flex" alignItems="center" gap="spacing.4">
          <Box flex={1}>
            <Text size="small" marginBottom="spacing.4">
              Fee credits are the credits using which you can receive the full settlement amount
              without any fee deduction. Specific business categories also use these credits to help
              them meet regulatory requirements.
            </Text>
            <Text size="small" weight="semibold">
              For example:
            </Text>
            <Text size="small">
              If you have a fee credit of ₹100.00, all the transactions will be settled in full, and
              the fees for these payments will be deducted from the ₹100 fee credit.
            </Text>
          </Box>
          <ImageContainer url="/dist/css/assets/exporter-rewards/rewards-info-example.svg" />
        </Box>
      </Card>
      <Card>
        <Heading marginBottom="spacing.4">
          How do I increase my International payments GMV on Razorpay
        </Heading>
        <Box display="flex" alignItems="center" gap="spacing.4">
          <Box flex={1}>
            <Text size="small" marginBottom="spacing.3">
              Here are a few tips and tricks to increase international payments GMV on Razorpay:
            </Text>
            <List size="small">
              <ListItem>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum dictum, nisi et
                maximus
              </ListItem>
              <ListItem>
                aliquam, tellus mi dignissim tellus, ut cursus orci ipsum vel lorem. Duis sed nisi
                lacus.
              </ListItem>
              <ListItem>
                Nam imperdiet molestie eros, eu ornare urna porta vitae. Curabitur congue sit amet
                leo consectetur ullamcorper.
              </ListItem>
              <ListItem>Donec sed venenatis nulla, a tristique augue.</ListItem>
            </List>
          </Box>
          <ImageContainer url="/dist/css/assets/exporter-rewards/rewards-info-points.svg" />
        </Box>
      </Card>
    </Container>
  );
};

export default RewardsInfo;
