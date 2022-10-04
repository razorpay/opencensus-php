export const videoTypesMap = {
  '3g2': 'video/3gpp2',
  '3gp': 'video/3gpp',
  avi: 'video/x-msvideo',
  flv: 'video/x-flv',
  h264: 'video/mp4',
  m4v: 'video/m4v',
  mkv: 'video/x-matroska',
  mov: 'video/quicktime',
  mp4: 'video/mp4',
  mpg: 'video/mpeg',
  mpeg: 'video/mpeg',
  rm: 'application/vnd.rn-realmedia',
  wmv: 'video/x-ms-wmv',
};

export const allowedVideoExtensions = Object.keys(videoTypesMap);
