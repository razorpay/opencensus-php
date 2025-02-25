import crypto from 'crypto';
import { COOKIE_ENCRYPTION_SECRET } from '@apps/shell/src/env';

const IV_LENGTH = 16; // For AES, this is always 16

export const encrypt = (text: string): string => {
  let iv = crypto.randomBytes(IV_LENGTH);
  let cipher = crypto.createCipheriv(
    'aes-256-cbc',
    Buffer.from(COOKIE_ENCRYPTION_SECRET, 'hex'),
    iv,
  );
  let encrypted = cipher.update(text);

  encrypted = Buffer.concat([encrypted, cipher.final()]);

  return iv.toString('hex') + ':' + encrypted.toString('hex');
};

export const decrypt = (text: string): string => {
  let textParts = text.split(':');
  let iv = Buffer.from(textParts.shift()!, 'hex');
  let encryptedText = Buffer.from(textParts.join(':'), 'hex');
  let decipher = crypto.createDecipheriv(
    'aes-256-cbc',
    Buffer.from(COOKIE_ENCRYPTION_SECRET, 'hex'),
    iv,
  );
  let decrypted = decipher.update(encryptedText);

  decrypted = Buffer.concat([decrypted, decipher.final()]);

  return decrypted.toString();
};
