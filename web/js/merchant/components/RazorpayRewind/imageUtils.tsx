import fileDownload from 'common/utils/file-download';

import { copyImgToClipboard, postContent } from './utils';

const imageType = 'image/png';

const trimCanvas = (canvas, trimX, trimBottom) => {
  const trimmedWidth = canvas.width - 2 * trimX;
  const trimmedHeight = canvas.height - trimBottom;

  if (trimmedWidth <= 0 || trimmedHeight <= 0) {
    return null;
  }

  // Create a new canvas for the trimmed image
  const trimmedCanvas = document.createElement('canvas');
  trimmedCanvas.width = trimmedWidth;
  trimmedCanvas.height = trimmedHeight;

  const context = trimmedCanvas.getContext('2d');
  if (!context) {
    return null;
  }

  // Draw the trimmed image
  context.drawImage(
    canvas,
    trimX,
    0,
    trimmedWidth,
    trimmedHeight,
    0,
    0,
    trimmedWidth,
    trimmedHeight,
  );

  return trimmedCanvas;
};

const captureImage = (componentRef, isMobileOrTablet, action): Promise<void> | undefined => {
  if (!componentRef.current) {
    return;
  }
  import('html2canvas').then(({ default: html2canvas }) => {
    html2canvas(componentRef.current, {
      allowTaint: true,
      useCORS: true,
    }).then((canvas) => {
      // Trimming the canvas from each side due to carousel buttons & indicators
      const trimWidth = isMobileOrTablet ? 0 : 80;
      const trimHeight = isMobileOrTablet ? 90 : 30;
      const trimmedCanvas = trimCanvas(canvas, trimWidth, trimHeight);
      if (!trimmedCanvas) {
        return null;
      }
      trimmedCanvas.toBlob(
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