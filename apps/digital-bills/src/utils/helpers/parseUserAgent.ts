import UAParser from 'ua-parser-js';

type ParsedUserAgent = {
  browser: string;
  os: string;
  device: string;
};

const parseUserAgent = (userAgent: string): ParsedUserAgent => {
  const parsedUA = new UAParser(userAgent);
  return {
    browser: parsedUA.getBrowser().name,
    os: parsedUA.getOS().name,
    device: parsedUA.getDevice().model,
  };
};

export default parseUserAgent;
