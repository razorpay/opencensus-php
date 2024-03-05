import { getItem, setItem } from './localStorage';

let mode: ModeT = 'test';
export type ModeT = 'test' | 'live';

export function getMode(merchantId?: string): string {
  const _mode = getItem(`rzp_mode--${merchantId}`) || mode;
  return _mode;
}

export function setMode(value: ModeT): ModeT {
  mode = value;
  return mode;
}

export function switchMode(merchantId: string, _mode: ModeT): void {
  // eslint-disable-next-line no-useless-catch
  try {
    setItem(`rzp_mode--${merchantId}`, _mode);
  } catch (e) {
    throw e;
  }
}
