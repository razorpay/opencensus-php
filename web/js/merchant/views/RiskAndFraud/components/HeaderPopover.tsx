import React, { useState } from 'react';
import { Box, Text, Popover, Button } from '@razorpay/blade/components';

import Image from 'common/ui/Image';

import { StyledButtonText } from './styled';

interface IProps {
  text: string;
  title: string;
  content: string;
  contentImage: string;
  imageAlt: string;
}

const HeaderPopover = (props: IProps): JSX.Element => {
  const { text, title, content, contentImage, imageAlt } = props;
  const [isOpen, setIsOpen] = useState(false);

  const handleClick = () => setIsOpen(true);
  const handleClose = () => setIsOpen(false);

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
          <Button size="small" variant="primary" onClick={handleClose}>
            Read More
          </Button>
        </Box>
      }
    >
      <StyledButtonText onClick={handleClick}>{text}</StyledButtonText>
    </Popover>
  );
};

export default HeaderPopover;
