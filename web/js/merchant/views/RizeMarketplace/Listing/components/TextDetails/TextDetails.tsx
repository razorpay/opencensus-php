import React from 'react';
import { Box, Link, Text, Theme, Title } from '@razorpay/blade/components';
import styled from 'styled-components';

import { TextDetailsProps } from './types';

const LINK_REGEX = /(https?:\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z]{2,3}[^\s\t\n()[\]{}.,]*)/g;

const withLinkRendering = (text: string): React.ReactNode[] =>
  text
    .trim()
    .split(LINK_REGEX)
    .map((text) => [text, !!text.match(LINK_REGEX)] as const)
    .map(([text, isLink], idx) =>
      isLink ? (
        <Link href={text} target="_blank" size="large" key={idx}>
          {text}
        </Link>
      ) : (
        <React.Fragment key={idx}>{text}</React.Fragment>
      ),
    );

/**
 * Design demands a list style different from what Blade provides to match
 * the rest of the design better. Hence, a custom styled List and ListItem is created here
 */
const List = styled.ul`
  padding-left: 18px;
`;

const ListItem = styled.li(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.normal.lowContrast};
  font-size: ${theme.typography.fonts.size[300]}px;
  margin-top: ${theme.spacing[3]}px;
`,
);

const TextDetails = ({ heading, body }: TextDetailsProps): JSX.Element => (
  <Box>
    <Box marginBottom="spacing.5">
      <Title as="h2" size="small" color="surface.text.normal.lowContrast">
        {heading}
      </Title>
    </Box>
    {Array.isArray(body) ? (
      <List>
        {body.map((listItem, i) => (
          <ListItem key={`list-item-${i}`}>
            <Text size="large">
              {listItem
                .trim()
                .split('\n')
                .map((text, idx) => (
                  <React.Fragment key={idx}>
                    {withLinkRendering(text)} <br />
                  </React.Fragment>
                ))}
            </Text>
          </ListItem>
        ))}
      </List>
    ) : (
      body
        .trim()
        .split('\n')
        .map((text, i) => (
          <Text key={i} size="large" marginTop="spacing.3">
            {withLinkRendering(text)}
          </Text>
        ))
    )}
  </Box>
);

export default TextDetails;
