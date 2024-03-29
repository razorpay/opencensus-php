import React from 'react';
import {
  Box,
  Heading,
  Text,
  Button,
  Card,
  CardBody,
  ArrowRightIcon,
} from '@razorpay/blade/components';
import ImgPlaceholder from 'assets/reconciliations/img-placeholder.png';
import RecIcon from 'assets/reconciliations/recon-icon.svg';
import styled from 'styled-components';

const Img = styled.img`
  width: 100%;
`;
const LogoImg = styled.img`
  margin-right: 4px;
`;

const About = ({ handleCtaClick }) => {
  return (
    <Card margin="spacing.6">
      <CardBody>
        <Box display="flex">
          <Box width="50%">
            <Img src={ImgPlaceholder} alt="placeholder" />
          </Box>
          <Box width="50%" display="flex" alignItems="center" padding="spacing.10">
            <Box>
              <LogoImg
                width="92"
                src="https://cdn.razorpay.com/logo.svg"
                role="img"
                aria-label="brand-logo"
                alt="brand-logo"
              />
              <img src={RecIcon} alt="icon" />
              <Box marginTop="spacing.6" />
              <Heading weight="regular" marginTop="spacing.6" size="xlarge">
                Redefining Reconciliation with
              </Heading>
              <Heading weight="semibold" marginTop="spacing.8" size="xlarge">
                Proactive Insights
              </Heading>
              <Text marginTop="spacing.6" color="surface.text.gray.muted">
                Automated reconciliations solution crafted to simplify end-to-end recon processes
                for businesses.
              </Text>
              <Box display="flex" marginTop="spacing.6" alignItems="center">
                <Button
                  iconPosition="right"
                  icon={ArrowRightIcon}
                  marginRight="spacing.8"
                  onClick={handleCtaClick}
                >
                  Get Started
                </Button>
                {/* Enabled once recon page is created
                <Link
                  iconPosition="right"
                  // href="https://razorpay.com/reconciliations"
                  target="_blank"
                  rel="noopener noreferer"
                >
                  Know More <img src={KnowMoreIcon} alt="icon" />
                </Link> */}
              </Box>
            </Box>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default About;
