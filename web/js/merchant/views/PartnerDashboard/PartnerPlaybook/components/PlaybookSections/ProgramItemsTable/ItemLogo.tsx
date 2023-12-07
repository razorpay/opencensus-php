import React from 'react';
import { Box } from '@razorpay/blade/components';

import FileTypeDocIcon from 'assets/partner-dashboard/partner-playbook/file-type-doc.svg';
import FileTypeImageIcon from 'assets/partner-dashboard/partner-playbook/file-type-image.svg';
import FileTypeMiscIcon from 'assets/partner-dashboard/partner-playbook/file-type-misc.svg';
import FileTypePDFIcon from 'assets/partner-dashboard/partner-playbook/file-type-pdf.svg';
import FileTypePPTIcon from 'assets/partner-dashboard/partner-playbook/file-type-ppt.svg';
import FileTypeVideoIcon from 'assets/partner-dashboard/partner-playbook/file-type-video.svg';
import {
  PlaybookContentTypes,
  ProgramItem,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';

const contentTypeToLogo: Record<PlaybookContentTypes, { src: TODO_PD; alt: string }> = {
  PDF: { src: FileTypePDFIcon, alt: 'PDF File' },
  PPT: { src: FileTypePPTIcon, alt: 'PPT File' },
  VIDEO: { src: FileTypeVideoIcon, alt: 'Video File' },
  DOC: { src: FileTypeDocIcon, alt: 'Doc File' },
  IMAGE: { src: FileTypeImageIcon, alt: 'Image File' },
};

type ItemLogoProps = {
  item: ProgramItem;
};
const ItemLogo = ({ item: { content_type } }: ItemLogoProps): JSX.Element => {
  const logoData = contentTypeToLogo[content_type] || { src: FileTypeMiscIcon, alt: 'Misc File' };
  return <Box>{logoData ? <img src={logoData.src} alt={logoData.alt} /> : null}</Box>;
};

export default ItemLogo;
