import React, { useState } from 'react';
import {
  Box,
  CopyIcon,
  DownloadIcon,
  EyeIcon,
  IconButton,
  Link,
  Tooltip,
} from '@razorpay/blade/components';

import copyToClipboard from 'common/utils/copyToClipboard';
import {
  PlaybookContentTypes,
  ProgramItem,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';
const { DOC, PDF, PPT, IMAGE, VIDEO } = PlaybookContentTypes;

type ItemActionsProps = {
  item: ProgramItem;
  openItemPreview: (item: ProgramItem) => void;
  trackItemActionCta: (ctaClicked: string, item: ProgramItem) => void;
};

const ItemActions = ({
  item,
  trackItemActionCta,
  openItemPreview,
}: ItemActionsProps): JSX.Element => {
  const { content_type, copy_url, download_url } = item;
  const [isCopied, setIsCopied] = useState(false);
  const onCopyClick = (e) => {
    e.stopPropagation();
    copyToClipboard(copy_url);
    setIsCopied(true);
    trackItemActionCta('copy', item);
  };
  const onViewClick = (e) => {
    e.stopPropagation();
    trackItemActionCta('view', item);
    setIsCopied(false);
    openItemPreview(item);
  };
  const onDownloadClick = (e) => {
    e.stopPropagation();
    setIsCopied(false);
    trackItemActionCta('download', item);
  };
  const shouldShowCopyButton = [VIDEO].includes(content_type);
  const shouldShowDownloadButton =
    !item.disable_download && [DOC, PDF, PPT, IMAGE].includes(content_type);
  return (
    <Box display="flex" flexDirection="row" gap="spacing.7" justifyContent="center">
      <IconButton
        icon={() => <EyeIcon size="large" color="interactive.icon.primary.normal" />}
        accessibilityLabel="view"
        onClick={onViewClick}
      />
      {shouldShowCopyButton ? (
        <Tooltip
          content={isCopied ? 'Copied!' : 'Copy'}
          onOpenChange={function noRefCheck() {}}
          placement="bottom"
          title=""
        >
          <IconButton
            icon={() => <CopyIcon size="large" color="interactive.icon.primary.normal" />}
            accessibilityLabel="Copy"
            onClick={onCopyClick}
          />
        </Tooltip>
      ) : null}
      {shouldShowDownloadButton ? (
        <Link href={download_url} target="_blank" download rel="noreferrer noopener">
          {/* eslint-disable-next-line */}
          {/* @ts-ignore TS2322 Link only accepts string children */}
          <IconButton
            icon={() => <DownloadIcon size="large" color="interactive.icon.primary.normal" />}
            accessibilityLabel="download"
            onClick={onDownloadClick}
          />
        </Link>
      ) : null}
    </Box>
  );
};

export default ItemActions;
