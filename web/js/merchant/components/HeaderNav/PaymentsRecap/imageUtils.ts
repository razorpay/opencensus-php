import fileDownload from 'common/utils/file-download';

import { copyImgToClipboard, postContent } from './utils';

const imageType = 'image/png';

const captureImage = (componentRef, imgDimension, action): Promise<void> | undefined => {
  if (!componentRef.current) {
    return;
  }
  import('html2canvas').then(({ default: html2canvas }) => {
    html2canvas(componentRef.current, {
      allowTaint: true,
      useCORS: true,
      // height: imgDimension + 100,
    }).then((canvas) => {
      canvas.toBlob(
        async (blob) => {
          if (!blob) return;
          if (action === 'copy') {
            await copyImgToClipboard(blob, imageType);
          } else if (action === 'download') {
            fileDownload(blob, 'rewind.png', imageType);
          } else if (action === 'navigator-share') {
            const file = new File([blob], 'rewind.png', {
              type: imageType,
            });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
              try {
                await navigator.share({
                  text: postContent,
                  files: [file],
                  title: 'RazorpayRewind',
                });
              } catch (e) {
                console.warn('Error sharing:', e);
              }
            }
          }
        },
        imageType,
        1,
      );
    });
  });
};

export { captureImage };
