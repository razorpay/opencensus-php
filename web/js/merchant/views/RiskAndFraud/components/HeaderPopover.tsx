import React, { useState } from 'react';
import { Box, Text, Popover, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import Image from 'common/ui/Image';

import { StyledButtonText } from './styled';
import { AnalyticsEntity } from '../RiskAnalytics/types';
import { trackEvent } from '../common/trackEvents';
import { getDocLink } from '../common/utils';

interface IProps {
  entity: AnalyticsEntity;
  text: string;
  title: string;
  content: string;
  contentImage: string;
  imageAlt: string;
  docLink: string;
  businessName: string;
}

const HeaderPopover = (props: IProps): JSX.Element => {
  const { entity, text, title, content, contentImage, imageAlt, docLink, businessName } = props;
  const [isOpen, setIsOpen] = useState(false);

  const handleClick = () => setIsOpen(true);

  const handleLink = () => {
    const link = getDocLink(docLink, businessName);
    trackEvent({
      objectName: 'Read More',
      properties: {
        section: entity,
        link,
      },
    });
    window.open(link, '_blank', 'noopener,noreferrer');
  };

  return (
    <Popover
      isOpen={isOpen}
      placement="right-start"
      onOpenChange={({ isOpen }) => {
        if (!isOpen) {
          setIsOpen(false);
        }
      }}
      title={title}
      content={
        <Box>
          <Text>{content}</Text>
          {contentImage && <Image src={contentImage} alt={imageAlt} />}
        </Box>
      }
      footer={
        <Box display="flex" flexDirection="row" justifyContent="flex-end">
          <Button size="small" variant="primary" onClick={handleLink}>
            Read More
          </Button>
        </Box>
      }
    >
      <StyledButtonText onClick={handleClick}>{text}</StyledButtonText>
    </Popover>
  );
};

const mapStateToProps = (state) => ({
  businessName: state.session.org?.business_name,
});

export default connect(mapStateToProps)(HeaderPopover);
