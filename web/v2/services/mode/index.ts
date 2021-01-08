let mode: ModeT = 'test';
export type ModeT = 'test' | 'live';

export function getMode(): string {
  return mode;
}

export function setMode(value: ModeT): ModeT {
  mode = value;
  return mode;
}

export function switchMode(merchantId: string, _mode: ModeT): void {
  // eslint-disable-next-line no-useless-catch
  try {
    localStorage.setItem(`rzp_mode--${merchantId}`, _mode);
  } catch (e) {
    throw e;
  }
}
