import {
  Block,
  Blocks,
  Tag,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';
import sendToSegment from '.';

export default function blockAnalytics(blocks: Blocks) {
  const comingSoonFeatures: string[] = [];
  const newTagFeatures: string[] = [];
  blocks?.forEach((block: Block) => {
    block?.tags.forEach((tagData: Tag) => {
      if (tagData.tag === 'coming soon') {
        if (!comingSoonFeatures.includes(tagData.name)) {
          comingSoonFeatures.push(tagData.name);
        }
      } else if (tagData.tag === 'new') {
        if (!newTagFeatures.includes(block.name)) {
          newTagFeatures.push(block.name);
        }
      }
    });
  });
  sendToSegment(
    'coming soon features',
    'loaded',
    { option: comingSoonFeatures },
    'Checkout features',
    'feature-blocks',
  );
  sendToSegment(
    'new features displayed',
    'loaded',
    { option: newTagFeatures },
    'Checkout features',
    'feature-blocks',
  );
}
